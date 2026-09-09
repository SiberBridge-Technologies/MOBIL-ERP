# CommonJS compatibility build

Source: decode-uri-component 0.5.0 (MIT), https://github.com/SamVerschueren/decode-uri-component/releases/tag/v0.5.0

The only source change is replacing the default ES export with module.exports.
query-string 7 uses require() and cannot call the upstream ESM namespace object.
The 0.5.0 decoding algorithm fixes GHSA-vcc3-ghjq-m6fr. Keep this adapter until
the navigation dependency supports the upstream module format.
