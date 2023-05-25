const {Binance} = require("./Exchanges/Binance");

console.log((new Binance).isSymbolOnline('BTC:USDT'))
