document.getElementById('generateBtcButton').addEventListener('click', generateBitcoinWallet);


function generateBitcoinWallet() {
    const EC = window.elliptic.ec; // Ensure elliptic is referenced correctly
    const ec = new EC('secp256k1');
    const keyPair = ec.genKeyPair();
    
    const privateKey = keyPair.getPrivate('hex');
    const publicKey = keyPair.getPublic('hex');
    
    const { address } = getBitcoinAddress(publicKey);
    
    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = publicKey;
    document.getElementById('privateKey').textContent = privateKey;
    document.getElementById('keys').style.display = 'block';
}

function getBitcoinAddress(publicKey) {
    const sha256 = CryptoJS.SHA256(CryptoJS.enc.Hex.parse(publicKey));
    const ripemd160 = CryptoJS.RIPEMD160(sha256);
    
    // Add network byte (0x00 for mainnet)
    const networkByte = CryptoJS.enc.Hex.parse('00');
    const networkAndPubKeyHash = networkByte.concat(ripemd160);
    
    // Double SHA-256
    const checksum = CryptoJS.SHA256(CryptoJS.SHA256(networkAndPubKeyHash)).toString(CryptoJS.enc.Hex).substr(0, 8);
    const binaryAddress = networkAndPubKeyHash.concat(CryptoJS.enc.Hex.parse(checksum));
    
    // Convert to base58
    const address = base58Encode(binaryAddress.toString(CryptoJS.enc.Hex));
    
    return { address };
}

function base58Encode(hex) {
    const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    const BASE = ALPHABET.length;

    let num = BigInt(`0x${hex}`);
    let encoded = '';

    while (num > 0) {
        const remainder = num % BigInt(BASE);
        num = num / BigInt(BASE);
        encoded = ALPHABET[Number(remainder)] + encoded;
    }

    // Append leading '1's for each leading 00 byte
    for (let i = 0; i < hex.length && hex.substr(i, 2) === '00'; i += 2) {
        encoded = '1' + encoded;
    }

    return encoded;
}
