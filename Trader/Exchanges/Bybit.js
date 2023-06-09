const {
    InverseClient,
    LinearClient,
    InverseFuturesClient,
    SpotClientV3,
    UnifiedMarginClient,
    USDCOptionClient,
    USDCPerpetualClient,
    AccountAssetClient,
    CopyTradingClient,
    RestClientV5,
} = require('bybit-api');
const Exchange = require('../Exchanges.js');
const path = require('path');
const {CategoryV5, OrderSideV5, OrderTypeV5} = require("bybit-api/lib/types/v5-shared");
require('dotenv').config({path:path.join(__dirname,'..','..','/.env')});

class Bybit extends Exchange {
    constructor() {
        super();
        this.sdk = new RestClientV5({
                key: process.env.BYBIT_API_KEY,
                secret: process.env.BYBIT_API_SECRET
            })
    }

    async isSymbolOnline(symbol) {
        try {
            let symbolData = await this.sdk.getInstrumentsInfo({
                symbol:this.normalize(symbol),
                category:'spot'
            })
            return symbolData.result.list[0].status === 'Trading'
        }catch (e) {
            return false
        }
    }

    async orderBook(symbol) {
        const data = await this.sdk.getOrderbook({
            symbol:this.normalize(symbol),
            category:"spot",
            limit:process.env.DEPTH
        });
        return this.extractBook(data.result);
    }

    async sendOrder(data) {
        return await this.sdk.submitOrder({
            category: 'spot',
            symbol: data.symbol,
            side: data.side,
            orderType: 'Market',
            qty: data.volume
        });
    }

    async transfer(data) {
        return await this.sdk.submitWithdrawal({
            coin:data.coin,
            address:data.address,
            amount:data.volume,
            chain:performance.now()
        })
    }

    extractBook(data) {
        const book = {
            asks:[],
            bids:[]
        };
        let i = 0;
        const count = Math.min(data['a'].length, data['b'].length);

        while (i < count) {
            book.asks.push({
                'price': data['a'][i][0],
                'value': data['a'][i][1]
            });
            book.bids.push({
                'price': data['b'][i][0],
                'value': data['b'][i][1]
            });

            i++;
        }

        return book;
    }

    async coin(coin) {
        console.log((await this.sdk.getCoinInfo(coin)).result.rows)
    }
}

module.exports = Bybit
