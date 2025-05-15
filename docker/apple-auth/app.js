const express = require('express'); // npm install express
const crypto = require('crypto');
const pick = require('./webpack')
const app = express();
app.use(express.json());

const storage = {};

function generateRandomKey() {
    return crypto.randomBytes(4).toString('hex');
}

window = global;

function o(t) {
    return function(t) {
        if (Array.isArray(t))
            return i(t)
    }(t) || function(t) {
        if ("undefined" != typeof Symbol && null != t[Symbol.iterator] || null != t["@@iterator"])
            return Array.from(t)
    }(t) || function(t, r) {
        if (!t)
            return;
        if ("string" == typeof t)
            return i(t, r);
        var e = Object.prototype.toString.call(t).slice(8, -1);
        "Object" === e && t.constructor && (e = t.constructor.name);
        if ("Map" === e || "Set" === e)
            return Array.from(t);
        if ("Arguments" === e || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(e))
            return i(t, r)
    }(t) || function() {
        throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")
    }()
}

app.post('/init', (req, res) => {
    const { email } = req.body;
    console.log(req.body)
    if (!email) {
        return res.status(400).json({ error: 'Email is required' });
    }
    const n = pick(848)

    var key = generateRandomKey();
    const r = new n.a(email)

    var f = r.publicValue
    storage[key] = r;
    res.json({ key, value: btoa(String.fromCharCode.apply(String, o(new Uint8Array(f.buffer))))}); // Return the key and the value
});

app.post('/complete', (req, res) => {
    const { key,value } = req.body;
    console.log(key,value)
    const r = storage[key];
    const h = value;
    const t = pick(0).Buffer
    var l = h.iteration,
        p = h.b,
        v = h.salt,
        d = h.c,
        g = h.password,
        y = h.protocol;
        var m = {
            iterations: l,
            serverPublicValue: new Uint8Array(t.from(p, "base64")),
            salt: new Uint8Array(t.from(v, "base64")),
            password: g,
            protocol: y
        }
   r.getEvidenceMessage(m).then(e=>{
        console.log(e)
        e.c = d
       delete storage[key]
        res.json(e)
    })

});


const PORT = process.env.PORT || 8088;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
