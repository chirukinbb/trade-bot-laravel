const {
    SpotClient,
    FuturesClient,
    BrokerClient,
} = require('bitget-api');
const Exchange = require('../Exchanges.js');
const path = require('path');
require('dotenv').config({path:path.join(__dirname,'..','..','/.env')});

class Bitget extends Exchange {
    constructor() {
        super();
        this.sdk = new SpotClient({
            apiKey: process.env.BITGET_API_KEY,
            apiSecret: process.env.BITGET_API_SECRET,
            apiPass: process.env.BITGET_PASS_PHRASE
        })
    }

    async isSymbolOnline(symbol) {
        try {
            let symbolData = await this.sdk.getSymbol(this.normalize(symbol))
            return symbolData.data.status === 'online'
        }catch (e) {
            return false
        }
    }

    async orderBook(symbol) {
        const data = await this.sdk.getDepth(this.normalize(symbol),'step0',process.env.DEPTH);
        return this.extractBook(data.data);
    }

    async sendOrder(data) {
        return await this.sdk.submitOrder({
            symbol: data.symbol,
            side: data.side,
            orderType: 'market',
            quantity: data.volume,
            force:'normal'
        });
    }

    async transfer(data) {
        return await this.sdk.withdraw({
            coin:data.coin,
            address:data.address,
            amount:data.volume,
            chain:performance.now()
        })
    }

    normalize(symbol) {
        return symbol.replace(':', '')+'_SPBL';
    }
}

module.exports = Bitget
