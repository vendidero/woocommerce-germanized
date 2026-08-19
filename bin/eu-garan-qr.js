var QRCode = require( "qrcode-svg" );

var qrcode = new QRCode({
    content: "https://europa.eu/youreurope/commercial-guarantee-durability",
    padding: 0,
    width: 512,
    height: 512,
    color: "#010101",
    background: "#ffffff",
    join: true,
    ecl: "M",
});

qrcode.save("assets/images/garan-label/garan-label-qr.svg", function(error) {
    if (error) throw error;
    console.log("Done!");
});