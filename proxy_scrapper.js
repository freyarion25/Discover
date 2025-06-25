const fs = require("fs")
const path = require("path")
const utils = require("./lib/utils")

// Import scraper modules
const fineproxy = require("./scrapper/fineproxy")
const ProxyDB = require("./scrapper/ProxyDB")
const FreeProxyList = require("./scrapper/FreeProxyList")
const ProxyScrape = require("./scrapper/ProxyScrape")
const IPRoyal = require("./scrapper/IPRoyal")
const xreverselabs = require("./scrapper/xreverselabs")
const ProxyRack = require("./scrapper/ProxyRack")
const ProxyBros = require("./scrapper/ProxyBros")
const geonode = require("./scrapper/geonode")
const randomProxyUrls = require("./scrapper/random_proxy")
const FreeProxyWorld = require("./scrapper/free-proxy-world")
const AdvancedName = require("./scrapper/advanced-name")
const ZeroHack = require("./scrapper/zero-hack")
const Proxiware = require("./scrapper/proxiware")

const initFiles = () => {
  if (!fs.existsSync(path.dirname(utils.outputFile))) {
    fs.mkdirSync(path.dirname(utils.outputFile), { recursive: true })
  }

  utils.writtenProxies = new Set()
  utils.writtenIndonesianProxies = new Set()
  utils.duplicatesFound = 0
  utils.indoDuplicatesFound = 0

  // Create fresh files
  fs.writeFileSync(utils.outputFile, "")
  fs.writeFileSync(utils.indonesianOutputFile, "")
}

const shuffleArray = (array) => {
  const newArray = [...array]
  for (let i = newArray.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[newArray[i], newArray[j]] = [newArray[j], newArray[i]]
  }
  return newArray
}

// Helper function to safely execute a scraper
const safeExecuteScraper = async (scraperModule, reportProgress) => {
  try {
    if (typeof scraperModule === "function") {
      return await scraperModule(reportProgress)
    } else if (scraperModule && typeof scraperModule.default === "function") {
      return await scraperModule.default(reportProgress)
    } else if (scraperModule && typeof scraperModule.run === "function") {
      return await scraperModule.run(reportProgress)
    } else if (scraperModule && typeof scraperModule.scrape === "function") {
      return await scraperModule.scrape(reportProgress)
    } else if (scraperModule && typeof scraperModule.getProxies === "function") {
      return await scraperModule.getProxies(reportProgress)
    } else {
      throw new Error("Could not determine how to execute this scraper module")
    }
  } catch (error) {
    console.error(`Error executing scraper: ${error.message}`)
    return { total: 0, valid: 0, indo: 0 }
  }
}

const createScraperWrapper = (scraperInfo, lineNumber, updateFn) => {
  return async () => {
    try {
      let currentCount = 0
      let displayCount = 0
      let updateInterval = null

      const reportProgress = (count) => {
        currentCount = count
      }

      updateInterval = setInterval(() => {
        if (displayCount < currentCount) {
          displayCount++
          updateFn(lineNumber, `${scraperInfo.name.padEnd(20)} >> found ${displayCount} proxies so far...`)
        }
      }, 10)

      const scraperModule = scraperInfo.module
      const result = await safeExecuteScraper(scraperModule, reportProgress)

      result.valid = result.total || 0

      clearInterval(updateInterval)
      updateFn(lineNumber, `${scraperInfo.name.padEnd(20)} >> got ${result.total || 0} proxies`)

      return { ...(result || { total: 0, valid: 0, indo: 0 }), name: scraperInfo.name }
    } catch (error) {
      updateFn(lineNumber, `${scraperInfo.name.padEnd(20)} >> error: ${error.message}`)
      return { total: 0, valid: 0, indo: 0, name: scraperInfo.name }
    }
  }
}

// Batch writing to avoid memory issues
let proxyWriteBuffer = []
let indoProxyWriteBuffer = []
const BATCH_SIZE = 1000

const flushBuffers = () => {
  if (proxyWriteBuffer.length > 0) {
    fs.appendFileSync(utils.outputFile, proxyWriteBuffer.join("\n") + "\n")
    proxyWriteBuffer = []
  }

  if (indoProxyWriteBuffer.length > 0) {
    fs.appendFileSync(utils.indonesianOutputFile, indoProxyWriteBuffer.join("\n") + "\n")
    indoProxyWriteBuffer = []
  }
}

// Modified utils.writeProxy function with batching
utils.writeProxy = (proxy, isIndonesian = false) => {
  if (!proxy) return false

  proxy = proxy.trim()

  if (utils.writtenProxies.has(proxy)) {
    utils.duplicatesFound++
    return false
  }

  utils.writtenProxies.add(proxy)
  proxyWriteBuffer.push(proxy)

  if (isIndonesian) {
    if (!utils.writtenIndonesianProxies.has(proxy)) {
      utils.writtenIndonesianProxies.add(proxy)
      indoProxyWriteBuffer.push(proxy)
    } else {
      utils.indoDuplicatesFound++
    }
  }

  // Flush buffers when they reach batch size
  if (proxyWriteBuffer.length >= BATCH_SIZE || indoProxyWriteBuffer.length >= BATCH_SIZE) {
    flushBuffers()
  }

  return true
}

const main = async () => {
  try {
    console.log(`
██████╗░██████╗░░█████╗░██╗░░██╗██╗░░░██╗  ░██████╗░█████╗░██████╗░░█████╗░██████╗░██████╗░███████╗██████╗░
██╔══██╗██╔══██╗██╔══██╗╚██╗██╔╝╚██╗░██╔╝  ██╔════╝██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔════╝██╔══██╗
██████╔╝██████╔╝██║░░██║░╚███╔╝░░╚████╔╝░  ╚█████╗░██║░░╚═╝██████╔╝███████║██████╔╝██████╔╝█████╗░░██████╔╝
██╔═══╝░██╔══██╗██║░░██║░██╔██╗░░░╚██╔╝░░  ░╚═══██╗██║░░██╗██╔══██╗██╔══██║██╔═══╝░██╔═══╝░██╔══╝░░██╔══██╗
██║░░░░░██║░░██║╚█████╔╝██╔╝╚██╗░░░██║░░░  ██████╔╝╚█████╔╝██║░░██║██║░░██║██║░░░░░██║░░░░░███████╗██║░░██║
╚═╝░░░░░╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝░░░╚═╝░░░  ╚═════╝░░╚════╝░╚═╝░░╚═╝╚═╝░░╚═╝╚═╝░░░░░╚═╝░░░░░╚══════╝╚═╝░░╚═╝

- Developed By Eclipse Security labs    
     `)
    console.log("🚀 Starting proxy scraper with Indonesian IP filtering")
    const startTime = Date.now()

    // Initialize fresh files
    initFiles()

    const allScrapers = [
      { id: 1, name: "Fineproxy", module: fineproxy },
      { id: 2, name: "ProxyDB", module: ProxyDB },
      { id: 3, name: "FreeProxyList", module: FreeProxyList },
      { id: 4, name: "ProxyScrape", module: ProxyScrape },
      { id: 5, name: "IPRoyal", module: IPRoyal },
      { id: 6, name: "xreverselabs", module: xreverselabs },
      { id: 7, name: "ProxyRack", module: ProxyRack },
      { id: 8, name: "ProxyBros", module: ProxyBros },
      { id: 9, name: "geonode", module: geonode },
      { id: 10, name: "Random URLs", module: randomProxyUrls },
      { id: 11, name: "FreeProxyWorld", module: FreeProxyWorld },
      { id: 12, name: "AdvancedName", module: AdvancedName },
      { id: 13, name: "ZeroHack", module: ZeroHack },
      { id: 14, name: "Proxiware", module: Proxiware },
    ]

    const shuffledScrapers = shuffleArray([...allScrapers])

    const updateLine = (lineNumber, text) => {
      process.stdout.write("\r")
      process.stdout.write(`\x1B[${lineNumber}A`)
      process.stdout.write("\x1B[2K")
      process.stdout.write(text)
      process.stdout.write(`\x1B[${lineNumber}B`)
      process.stdout.write("\r")
    }

    shuffledScrapers.forEach((scraper) => {
      console.log(`${scraper.name.padEnd(20)} >> running...`)
    })

    const originalConsoleLog = console.log
    const originalConsoleError = console.error
    const originalConsoleWarn = console.warn
    const originalConsoleInfo = console.info
    console.log = () => {}
    console.error = () => {}
    console.warn = () => {}
    console.info = () => {}

    const lineMap = {}
    shuffledScrapers.forEach((scraper, index) => {
      lineMap[scraper.id] = shuffledScrapers.length - index
    })

    const scraperPromises = shuffledScrapers.map((scraper) => {
      const lineNumber = lineMap[scraper.id]
      return createScraperWrapper(scraper, lineNumber, updateLine)()
    })

    const results = await Promise.all(scraperPromises)

    // Flush any remaining buffered data
    flushBuffers()

    console.log = originalConsoleLog
    console.error = originalConsoleError
    console.warn = originalConsoleWarn
    console.info = originalConsoleInfo

    let totalProxies = 0
    let validProxies = 0
    let indonesianProxies = 0

    for (const result of results) {
      totalProxies += result.total || 0
      validProxies += result.valid || 0
      indonesianProxies += result.indo || 0
    }

    const totalTimeElapsed = ((Date.now() - startTime) / 1000).toFixed(2)
    console.log("\n")
    console.log("━".repeat(80))
    console.log(`✅ Proxy scraping completed in ${totalTimeElapsed}s`)
    console.log("━".repeat(80))
    console.log(`📊 Total proxies found: ${totalProxies}`)
    console.log(`📊 Total proxies in file: ${utils.writtenProxies.size}`)
    console.log(`📊 Duplicates skipped: ${utils.duplicatesFound}`)
    console.log(`📊 Total Indonesian proxies: ${utils.writtenIndonesianProxies.size}`)
    console.log(`📊 Indonesian duplicates skipped: ${utils.indoDuplicatesFound}`)
    console.log("━".repeat(80))
    console.log(`📁 Saved To : ${utils.outputFile}`)
    console.log(`📁 Indonesian proxies : ${utils.indonesianOutputFile}`)
    console.log("\n📊 Results by scraper:")

    const sortedResults = results
      .filter((result) => result && typeof result.total === "number")
      .sort((a, b) => (b.total || 0) - (a.total || 0))

    sortedResults.forEach((result) => {
      console.log(
        `${result.name.padEnd(20)} >> got ${(result.total || 0).toString().padStart(5)} proxies (${result.valid || 0} valid)`,
      )
    })
  } catch (error) {
    console.error("❌ Fatal error:", error.message)
    console.error("Stack trace:", error.stack)
  }
}

const createSampleUrlsFile = () => {
  const filePath = "./scrapper/url/urls.txt"
  if (!fs.existsSync(filePath)) {
    if (!fs.existsSync("./scrapper/url")) {
      fs.mkdirSync("./scrapper/url", { recursive: true })
    }
    fs.writeFileSync(filePath, "")
    console.log(`Created sample ${filePath} file with common proxy URLs`)
  }
}

if (!fs.existsSync("./scrapper")) {
  fs.mkdirSync("./scrapper", { recursive: true })
}

createSampleUrlsFile()
main()
