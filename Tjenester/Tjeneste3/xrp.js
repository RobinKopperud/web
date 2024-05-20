document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('generateXrpButton').addEventListener('click', generateRippleWallet);
});

function generateRippleWallet() {
    const EC = window.elliptic.ec; // Ensure elliptic is referenced correctly
    const ec = new EC('secp256k1');
    const keyPair = ec.genKeyPair();
    
    const privateKey = keyPair.getPrivate('hex');
    const publicKey = keyPair.getPublic('hex');
    
    const address = getRippleAddress(publicKey);
    
    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = publicKey;
    document.getElementById('privateKey').textContent = privateKey;
    document.getElementById('keys').style.display = 'block';
}

function getRippleAddress(publicKey) {
    const publicKeyBuffer = Buffer.from(publicKey, 'hex');
    const sha256 = crypto.createHash('sha256').update(publicKeyBuffer).digest();
    const ripemd160 = crypto.createHash('ripemd160').update(sha256).digest();

    const payload = Buffer.concat([Buffer.from([0x00]), ripemd160]);
    const checksum = crypto.createHash('sha256').update(crypto.createHash('sha256').update(payload).digest()).digest().slice(0, 4);
    const addressBuffer = Buffer.concat([payload, checksum]);

    return base58Encode(addressBuffer);
}

function base58Encode(buffer) {
    const ALPHABET = 'rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz';
    const BASE = ALPHABET.length;

    let num = BigInt(`0x${buffer.toString('hex')}`);
    let encoded = '';

    while (num > 0) {
        const remainder = num % BigInt(BASE);
        num = num / BigInt(BASE);
        encoded = ALPHABET[Number(remainder)] + encoded;
    }

    // Append leading 'r' for each leading 00 byte
    for (let i = 0; i < buffer.length && buffer[i] === 0; i++) {
        encoded = 'r' + encoded;
    }

    return encoded;
}
