const { MainClient } = require('binance');
const { Exchange } = require('../Exchanges');
require('dotenv').config();

class Binance extends Exchange {
    constructor() {
        super();
        this.sdk = new MainClient({
            api_key: process.env.BINANCE_API_KEY,
            api_secret: process.env.BINANCE_API_SECRET
        });
        this.name = 'binance';
    }

    async isSymbolOnline(symbol) {
        let symbolData = await this.sdk.getExchangeInfo()
        symbolData = symbolData['symbols'].filter(data => data['symbol'] === this.normalize(symbol));

        return symbolData.length && symbolData[symbolData.length - 1]['status'] === 'TRADING';
    }

    orderBook(symbol) {
        const data = this.sdk.depth(this.normalize(symbol), process.env.DEPTH);

        return this.extractBook(data);
    }

    async sendOrder(data) {
        const timestamp = Math.floor(Date.now() / 1000);
        const signature = crypto.createHmac('sha256', process.env.BINANCE_API_SECRET).update(querystring.stringify(data)).digest('hex');
        const postData = querystring.stringify({
            'symbol': data['symbol'],
            'quantity': data['volume'],
            'side': data['side'],
            'type': 'MARKET',
            'timestamp': timestamp,
            'signature': signature
        });

        const response = await Http.post('https://testnet.binance.vision/sapi/v1/margin/order', postData);

        return JSON.parse(response.body()).orderId;
    }

    async order(data) {
        const timestamp = Math.floor(Date.now() / 1000);
        const signature = crypto.createHmac('sha256', process.env.BINANCE_API_SECRET).update(querystring.stringify(data)).digest('hex');
        const postData = querystring.stringify({
            'symbol': data['symbol'],
            'orderId': data['orderId'],
            'timestamp': timestamp,
            'signature': signature
        });

        const response = await Http.post('https://testnet.binance.vision/api/v3/order', postData);

        const responseData = JSON.parse(response.body());

        return {
            'volume': responseData['origQty'],
            'price': responseData['price'],
            'side': responseData['side']
        };
    }

    withdrawalFee(coin) {
        return this.sdk.withdrawFee(coin)['withdrawFee'];
    }

    extractBook(data) {
        const book = {};
        let i = 0;
        const count = Math.min(data['asks'].length, data['bids'].length);

        while (i < count) {
            const askPrice = Object.keys(data['asks'])[i];
            const bidPrice = Object.keys(data['bids'])[i];

            book['asks'].push({
                'price': askPrice,
                'value': data['asks'][askPrice]
            });
            book['bids'].push({
                'price': bidPrice,
                'value': data['bids'][bidPrice]
            });

            i++;
        }

        return book;
    }
}

module.exports = Binance
