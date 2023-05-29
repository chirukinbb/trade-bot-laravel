const Binance = require("./Exchanges/Binance");

let binance = new Binance

async function check() {
      let  data = await binance.withdrawalFee('BTC')
    console.log(data)
    console.log(performance.now()-t)
}

t=performance.now()
check()
