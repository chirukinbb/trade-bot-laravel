const { Http } = require('http');
const crypto = require('crypto');

class Bitget extends Exchange {
    constructor() {
        super();
        this.sdk = new BitgetSpot(process.env.BITGET_API_KEY, process.env.BITGET_API_SECRET);
        this.name = 'bitget';
    }

    symbols() {
        const response = JSON.parse(Http.get('https://api.bitget.com/api/mix/v1/market/contracts?productType=umcbl').body);
        return response.data.map(symbol => `${symbol.baseCoin}:${symbol.quoteCoin}`);
    }

    isSymbolOnline(symbol) {
        const response = JSON.parse(Http.get('https://api.bitget.com/api/mix/v1/market/contracts?productType=umcbl').body);
        const symbolData = response.data.filter(data => data.baseCoin + data.quoteCoin === this.normalize(symbol));
        return !isEmpty(symbolData) && symbolData[symbolData.length - 1].symbolStatus === 'normal';
    }

    orderBook(symbol) {
        const response = JSON.parse(Http.get(`https://api.bitget.com/api/mix/v1/market/depth?symbol=${this.normalize(symbol)}&limit=${process.env.DEPTH}`).body);
        return this.extractBook(response.data);
    }

    sendOrder(data) {
        const body = {
            'symbol': data['symbol'],
            'baseQuantity': data['volume'],
            'side': data['side'],
            'orderType': 'market',
            'loanType': 'normal',
            'timeInForce': 'gtc'
        };

        const headers = this.headers('POST', '/api/margin/v1/cross/order/placeOrder', body);
        const response = Http.withHeaders(headers).post('https://api.bitget.com/api/margin/v1/cross/order/placeOrder', body);

        return JSON.parse(response.body()).data.orderId;
    }

    order(data) {
        const params = {
            'symbol': data['symbol'],
            'orderId': data['id'],
            'startTime': 0
        };

        const headers = this.headers('POST', '/api/margin/v1/cross/order/fills', {});
        const response = Http.withHeaders(headers).get('https://api.bitget.com/api/margin/v1/cross/order/fills', params);

        const responseData = JSON.parse(response.body());
        return {
            'volume': responseData.resultList[0].fillQuantity,
            'price': responseData.resultList[0].fillPrice,
            'side': responseData.resultList[0].side
        };
    }

    sign(message, secret_key) {
        const mac = crypto.createHmac('sha256', secret_key).update(message).digest();
        return mac.toString('base64');
    }

    pre_hash(timestamp, method, request_path, body) {
        return timestamp + method.toUpperCase() + request_path + body;
    }

    headers(method, path, body) {
        const timestamp = Date.now();
        const message = this.pre_hash(timestamp.toString(), method, path, body);
        const signature = this.sign(message, process.env.BITGET_API_SECRET);
        return {
            'ACCESS-SIGN': signature,
            'ACCESS-KEY': process.env.BITGET_API_KEY,
            'ACCESS-TIMESTAMP': timestamp
        };
    }
}
