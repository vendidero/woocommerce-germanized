!function(){var e={};(function(e){Array.prototype.map||(Array.prototype.map=function(e){"use strict";if(null==this)throw new TypeError;var n=Object(this),F=n.length>>>0;if("function"!=typeof e)throw new TypeError;for(var t=new Array(F),r=arguments.length>=2?arguments[1]:void 0,i=0;i<F;i++)i in n&&(t[i]=e.call(r,n[i],i,n));return t});var n="A".charCodeAt(0),F="Z".charCodeAt(0);function t(e){return(e=(e=e.toUpperCase()).substr(4)+e.substr(0,4)).split("").map((function(e){var t=e.charCodeAt(0);return t>=n&&t<=F?t-n+10:e})).join("")}function r(e){for(var n,F=e;F.length>2;)n=F.slice(0,9),F=parseInt(n,10)%97+F.slice(n.length);return parseInt(F,10)%97}function i(e,n,F,t){this.countryCode=e,this.length=n,this.structure=F,this.example=t}i.prototype._regex=function(){return this._cachedRegex||(this._cachedRegex=(e=this.structure.match(/(.{3})/g).map((function(e){var n,F=e.slice(0,1),t=parseInt(e.slice(1),10);switch(F){case"A":n="0-9A-Za-z";break;case"B":n="0-9A-Z";break;case"C":n="A-Za-z";break;case"F":n="0-9";break;case"L":n="a-z";break;case"U":n="A-Z";break;case"W":n="0-9a-z"}return"(["+n+"]{"+t+"})"})),new RegExp("^"+e.join("")+"$")));var e},i.prototype.isValid=function(e){return this.length==e.length&&this.countryCode===e.slice(0,2)&&this._regex().test(e.slice(4))&&1==r(t(e))},i.prototype.toBBAN=function(e,n){return this._regex().exec(e.slice(4)).slice(1).join(n)},i.prototype.fromBBAN=function(e){if(!this.isValidBBAN(e))throw new Error("Invalid BBAN");var n=("0"+(98-r(t(this.countryCode+"00"+e)))).slice(-2);return this.countryCode+n+e},i.prototype.isValidBBAN=function(e){return this.length-4==e.length&&this._regex().test(e)};var A={};function o(e){A[e.countryCode]=e}o(new i("AD",24,"F04F04A12","AD1200012030200359100100")),o(new i("AE",23,"F03F16","AE070331234567890123456")),o(new i("AL",28,"F08A16","AL47212110090000000235698741")),o(new i("AT",20,"F05F11","AT611904300234573201")),o(new i("AZ",28,"U04A20","AZ21NABZ00000000137010001944")),o(new i("BA",20,"F03F03F08F02","BA391290079401028494")),o(new i("BE",16,"F03F07F02","BE68539007547034")),o(new i("BG",22,"U04F04F02A08","BG80BNBG96611020345678")),o(new i("BH",22,"U04A14","BH67BMAG00001299123456")),o(new i("BR",29,"F08F05F10U01A01","BR9700360305000010009795493P1")),o(new i("CH",21,"F05A12","CH9300762011623852957")),o(new i("CR",21,"F03F14","CR0515202001026284066")),o(new i("CY",28,"F03F05A16","CY17002001280000001200527600")),o(new i("CZ",24,"F04F06F10","CZ6508000000192000145399")),o(new i("DE",22,"F08F10","DE89370400440532013000")),o(new i("DK",18,"F04F09F01","DK5000400440116243")),o(new i("DO",28,"U04F20","DO28BAGR00000001212453611324")),o(new i("EE",20,"F02F02F11F01","EE382200221020145685")),o(new i("ES",24,"F04F04F01F01F10","ES9121000418450200051332")),o(new i("FI",18,"F06F07F01","FI2112345600000785")),o(new i("FO",18,"F04F09F01","FO6264600001631634")),o(new i("FR",27,"F05F05A11F02","FR1420041010050500013M02606")),o(new i("GB",22,"U04F06F08","GB29NWBK60161331926819")),o(new i("GE",22,"U02F16","GE29NB0000000101904917")),o(new i("GI",23,"U04A15","GI75NWBK000000007099453")),o(new i("GL",18,"F04F09F01","GL8964710001000206")),o(new i("GR",27,"F03F04A16","GR1601101250000000012300695")),o(new i("GT",28,"A04A20","GT82TRAJ01020000001210029690")),o(new i("HR",21,"F07F10","HR1210010051863000160")),o(new i("HU",28,"F03F04F01F15F01","HU42117730161111101800000000")),o(new i("IE",22,"U04F06F08","IE29AIBK93115212345678")),o(new i("IL",23,"F03F03F13","IL620108000000099999999")),o(new i("IS",26,"F04F02F06F10","IS140159260076545510730339")),o(new i("IT",27,"U01F05F05A12","IT60X0542811101000000123456")),o(new i("KW",30,"U04A22","KW81CBKU0000000000001234560101")),o(new i("KZ",20,"F03A13","KZ86125KZT5004100100")),o(new i("LB",28,"F04A20","LB62099900000001001901229114")),o(new i("LI",21,"F05A12","LI21088100002324013AA")),o(new i("LT",20,"F05F11","LT121000011101001000")),o(new i("LU",20,"F03A13","LU280019400644750000")),o(new i("LV",21,"U04A13","LV80BANK0000435195001")),o(new i("MC",27,"F05F05A11F02","MC5811222000010123456789030")),o(new i("MD",24,"U02F18","MD24AG000225100013104168")),o(new i("ME",22,"F03F13F02","ME25505000012345678951")),o(new i("MK",19,"F03A10F02","MK07250120000058984")),o(new i("MR",27,"F05F05F11F02","MR1300020001010000123456753")),o(new i("MT",31,"U04F05A18","MT84MALT011000012345MTLCAST001S")),o(new i("MU",30,"U04F02F02F12F03U03","MU17BOMM0101101030300200000MUR")),o(new i("NL",18,"U04F10","NL91ABNA0417164300")),o(new i("NO",15,"F04F06F01","NO9386011117947")),o(new i("PK",24,"U04A16","PK36SCBL0000001123456702")),o(new i("PL",28,"F08F16","PL61109010140000071219812874")),o(new i("PS",29,"U04A21","PS92PALS000000000400123456702")),o(new i("PT",25,"F04F04F11F02","PT50000201231234567890154")),o(new i("RO",24,"U04A16","RO49AAAA1B31007593840000")),o(new i("RS",22,"F03F13F02","RS35260005601001611379")),o(new i("SA",24,"F02A18","SA0380000000608010167519")),o(new i("SE",24,"F03F16F01","SE4550000000058398257466")),o(new i("SI",19,"F05F08F02","SI56263300012039086")),o(new i("SK",24,"F04F06F10","SK3112000000198742637541")),o(new i("SM",27,"U01F05F05A12","SM86U0322509800000000270100")),o(new i("TN",24,"F02F03F13F02","TN5910006035183598478831")),o(new i("TR",26,"F05A01A16","TR330006100519786457841326")),o(new i("VG",24,"U04F16","VG96VPVG0000012345678901")),o(new i("AO",25,"F21","AO69123456789012345678901")),o(new i("BF",27,"F23","BF2312345678901234567890123")),o(new i("BI",16,"F12","BI41123456789012")),o(new i("BJ",28,"F24","BJ39123456789012345678901234")),o(new i("CI",28,"U01F23","CI17A12345678901234567890123")),o(new i("CM",27,"F23","CM9012345678901234567890123")),o(new i("CV",25,"F21","CV30123456789012345678901")),o(new i("DZ",24,"F20","DZ8612345678901234567890")),o(new i("IR",26,"F22","IR861234568790123456789012")),o(new i("JO",30,"A04F22","JO15AAAA1234567890123456789012")),o(new i("MG",27,"F23","MG1812345678901234567890123")),o(new i("ML",28,"U01F23","ML15A12345678901234567890123")),o(new i("MZ",25,"F21","MZ25123456789012345678901")),o(new i("QA",29,"U04A21","QA30AAAA123456789012345678901")),o(new i("SN",28,"U01F23","SN52A12345678901234567890123")),o(new i("UA",29,"F25","UA511234567890123456789012345"));var w=/[^a-zA-Z0-9]/g,c=/(.{4})(?!$)/g;function a(e){return"string"==typeof e||e instanceof String}e.isValid=function(e){if(!a(e))return!1;e=this.electronicFormat(e);var n=A[e.slice(0,2)];return!!n&&n.isValid(e)},e.toBBAN=function(e,n){void 0===n&&(n=" "),e=this.electronicFormat(e);var F=A[e.slice(0,2)];if(!F)throw new Error("No country with code "+e.slice(0,2));return F.toBBAN(e,n)},e.fromBBAN=function(e,n){var F=A[e];if(!F)throw new Error("No country with code "+e);return F.fromBBAN(this.electronicFormat(n))},e.isValidBBAN=function(e,n){if(!a(n))return!1;var F=A[e];return F&&F.isValidBBAN(this.electronicFormat(n))},e.printFormat=function(e,n){return void 0===n&&(n=" "),this.electronicFormat(e).replace(c,"$1"+n)},e.electronicFormat=function(e){return e.replace(w,"").toUpperCase()},e.countries=A})(e),((window.germanized=window.germanized||{}).static=window.germanized.static||{}).iban=e}();