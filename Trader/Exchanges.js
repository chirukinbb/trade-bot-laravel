const config = require('./config');

class Exchange {
    constructor() {
        this.name = 'exchange';
    }

    async isSymbolOnline(symbol) {
        throw new Error('Method isSymbolOnline() must be implemented');
    }

    orderBook(symbol) {
        throw new Error('Method orderBook() must be implemented');
    }

    sendOrder(data) {
        throw new Error('Method sendOrder() must be implemented');
    }

    normalize(symbol) {
        return symbol.replace(':', '');
    }

    link(symbol) {
        const link = config.get('symbol.exchanges.' + this.name + '.link');
        return link.replace('{symbol}', this.normalize(symbol));
    }

    extractBook(data) {
        const book = {
            asks:[],
            bids:[]
        };
        let i = 0;
        const count = Math.min(data['asks'].length, data['bids'].length);

        while (i < count) {
            book.asks.push({
                'price': data['asks'][i][0],
                'value': data['asks'][i][1]
            });
            book.bids.push({
                'price': data['bids'][i][0],
                'value': data['bids'][i][1]
            });

            i++;
        }

        return book;
    }
}

module.exports = Exchange
