const fs = require('fs');
//// data ===========================================================

const DATA_FILE = './data-node.json';
const RESULTS_FILE = './data-results.json';

//// functions ===========================================================

// split the search string into an array so we can match word by word
function pair_parse(data) {
  // delimit start/end of SKU for additional pairs to improve accuracy & split
  data = data.trim(); // cleanup whitespace so our added replacement doesn't double up
  data = data.toLowerCase(); // case insensitive
  data = data.replace(/ /g, "="); // repalce spaces with = delimiter so we can also search part number strings in a desc
  //data = "=" + data + "="; // 'separate' part numbers with our new space replacement, finer granularity and desc matching
  data = data.split(''); // split the string into an array

  var pairs = [];

  // convert array of chars into array of paired chars
  // we go length - 1 since we use i+1 in the function
  for (var i = 0; i < data.length - 1; i++) {
    pairs[i] = data[i] + data[i + 1];
  }

  return pairs.sort();
}

function word_parse(desc) {
  desc = desc.split(" ");

  return desc;
}

function haystack_search(needles, haystack) {
  var matches = 0;

  for (let needle of needles) {
    for (let hay of haystack) {
      if (needle[0] < hay[0]) {
        break;
      }

      if (needle == hay) {
        matches++;
      }
    }
  }

  return matches;
}

function index_data(data) {
  for (let record of data) {
    record.index = pair_parse(record.desc).sort();
  }


  fs.writeFile(DATA_FILE, JSON.stringify(data), function(err) {
    if(err) {
      return console.log(err);
    }

    console.log("Data Indexed!");
  });
}

function search_desc(term, data) {
  let results = [];
  for(let record of data) {
    let matches = haystack_search(pair_parse(term), record.index);
    results.push({
      sku: record.sku,
      desc: record.desc,
      relevance: matches / record.index.length * 100,
    });
  }

  results.sort((a, b) => {
    return b.relevance - a.relevance;
  });

  fs.writeFile(RESULTS_FILE, JSON.stringify(results, null, 2), function(err) {
    if(err) {
      return console.log(err);
    }

    console.log("Results Created!");
  });
}



if(typeof process.argv[0] == 'undefined')
  console.error('Must provide a command');

const COMMAND = process.argv[2];
let DATA;

console.time("command");
switch (COMMAND) {
  case 'search' :
    DATA = require(DATA_FILE);
    if(typeof process.argv[3] == 'undefined')
      console.error('Must provide a search term');
    search_desc(process.argv[3], DATA);
  break;
  case 'index' :
    DATA = require(DATA_FILE);
    index_data(DATA);
  break;
  default:
    console.error('Not a valid command');
}
console.timeEnd("command")

// node node.js search "Chemical Glove - PVC - Large Acid Resistant"
