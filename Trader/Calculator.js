class Calculator {
    sell;
    buy;

    constructor(books) {
        this.bestPrices(books);
    }

    bestPrices(books) {
        let buyPrices = [];
        let sellPrices = [];

        books.map((book) => {
            buyPrices.push(book.book.bids[0].price);
            sellPrices.push(book.book.asks[0].price);
        });

        let buyPrice = Math.min(...buyPrices);
        let sellPrice = Math.max(...sellPrices);

        books.map((book) => {
            if (parseFloat(book.book.bids[0].price) === buyPrice) {
                this.buy = {
                    exchange: book.name,
                    price: buyPrice,
                    baseVolume: book.book.bids[0].value,
                };
            }
            if (parseFloat(book.book.asks[0].price) === sellPrice) {
                this.sell = {
                    exchange: book.name,
                    price: sellPrice,
                    baseVolume: book.book.asks[0].value,
                };
            }
        });
    }
}

module.exports = Calculator;

