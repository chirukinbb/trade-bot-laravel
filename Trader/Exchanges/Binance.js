const { MainClient } = require('binance');
const Exchange = require('../Exchanges.js');
const path = require('path');
require('dotenv').config({path:path.join(__dirname,'..','..','/.env')});

class Binance extends Exchange {
    constructor() {
        super();
        this.sdk = new MainClient({
            api_key: process.env.BINANCE_API_KEY,
            api_secret: process.env.BINANCE_API_SECRET
        })
    }

    async isSymbolOnline(symbol) {
        try {
            let symbolData = await this.sdk.getExchangeInfo({symbol:this.normalize(symbol)})
            return symbolData.symbols[0].status === 'TRADING';
        }catch (e) {
            return false
        }
    }

    async orderBook(symbol) {
        const data = await this.sdk.getOrderBook({
            symbol:this.normalize(symbol),
            limit:process.env.DEPTH
        });
        return this.extractBook(data);
    }

    async sendOrder(data) {
        return await this.sdk.submitNewOrder({
            symbol: data.symbol,
            side: data.side,
            type: 'MARKET',
            quantity: data.volume
        });
    }

    async withdrawalFee(coin) {
        try {
            let detail = await this.sdk.getAssetDetail({
                asset: coin
            });
            return detail[coin].withdrawFee
        } catch (e) {
            return e
        }
    }

    async transfer(data) {
        return await this.sdk.withdraw({
            coin:data.coin,
            address:data.address,
            amount:data.volume
        })
    }
}

module.exports = Binance
