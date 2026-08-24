# How (and why) EU GARAN default labels have been adjusted

## Inline styles, classes and ids

Removed `<style>` wrapper class-based CSS in favor of inline CSS styles added to the actual elements. This improves compatibility, e.g. with PDF engines.
Removed ids to prevent namespace collisions.

## Default QR Code in full label

The default QR Code included within the full label uses clip-paths which are not supported in most PDF engines. 
Remove the default QR Code and replace with a custom QR Code (linking to the same URL: https://europa.eu/youreurope/commercial-guarantee-durability) which uses rect elements.

Use

```shell
node bin/eu-garan-qr.js
```

to re-generate the QR Code.

## Embedded fonts

Embed base64-encoded via https://amio.github.io/embedded-google-fonts/ Inter font to support web rendering out-of-the box.


