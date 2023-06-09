const Binance = require("./Exchanges/Binance");
const Bitget = require("./Exchanges/Bitget");
const Bybit = require("./Exchanges/Bybit");
const Calculator = require("./Calculator");

let exchanges = [
    new Binance,
    new Bitget,
    new Bybit
],
    symbols = ['BTC:USDT']

setTimeout(() => {
    (new Bybit()).coin('BTC').then(r => {
        console.log(r)
    })
    // let p = performance.now()
    // symbols.map(async symbol => {
    //     let books = [],
    //         calculator
    //
    //     await Promise.all(
    //         exchanges.map(async exchange => {
    //             if (await exchange.isSymbolOnline(symbol)) {
    //                 books.push({
    //                     name: exchange.constructor.name,
    //                     book: await exchange.orderBook(symbol)
    //                 })
    //             }
    //         })
    //     )
    //     calculator = new Calculator(books)
    //     console.log(performance.now() - p,calculator.sell,calculator.buy)
    //})
},process.env.SECONDS_TIMEOUT * 1)
