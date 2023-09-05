const {
	WAConnection,
	MessageType,
	MessageOptions,
	Presence,
	Mimetype,
	WALocationMessage,
	WA_MESSAGE_STUB_TYPES,
	ReconnectMode,
	ProxyAgent,
	waChatKey,
} = require("@adiwajshing/baileys");
const http = require("http");
const https = require("https");
var qrcode = require('qrcode');
const fs = require("fs");
const { body, validationResult } = require('express-validator');
const express = require('express');
const app = express();
const server = http.createServer(app);
const socketIO = require('socket.io');
const { phoneNumberFormatter } = require('./helper/formatter');
const io = socketIO(server);
const request = require("request");
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
const cron = require('node-cron');
const mysql = require('mysql');
const { Socket } = require("dgram");
const date = new Date();
 
//konfigurasi koneksi
const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'wav3'
});
 
//connect ke database
db.connect((err) =>{
  if(err) throw err;
  console.log('Mysql Connected...');
});



// sesuaikan dengan domain yang digunakan
const configs = {
	port: 3000, // custom port to access server
	callback_url: 'http://localhost/wagw-mpedia-v3/helper/callback.php', // webhook url
	url_getkontak : 'http://localhost/wagw-mpedia-v3/helper/getkontak.php'
};
const conn = new WAConnection();
 conn.version = [2,2130,9];


const SESSION_FILE_PATH = './session.json'; 
let sessionCfg;
if (fs.existsSync(SESSION_FILE_PATH)) {
	sessionCfg = require(SESSION_FILE_PATH);
}


//conn.autoReconnect = ReconnectMode.onAllErrors;
conn.autoReconnect = ReconnectMode.onConnectionLost;
// conn.connectOptions = { reconnectID: "reconnect" };


async function connect() {
	if(conn.state != 'connecting'){
		fs.existsSync('./session.json') && conn.loadAuthInfo('./session.json');
		await conn.connect({ timeoutMs: 30 * 1000 });
		const authInfo = conn.base64EncodedAuthInfo(); // get all the auth info we need to restore this session
		fs.writeFileSync('./session.json', JSON.stringify(authInfo, null, '\t'))
		io.emit('message', 'Connected')
		io.emit('profile', conn.contacts);
		const nomors =  phoneNumberFormatter(conn.user.jid);
		const nomor = nomors.replace(/\D/g, '');
		io.emit('authenticated', conn.user)
	}
}



connect().catch((err) => {
	console.log(err);
});

io.on("connection", function (socket) {
	socket.on('ready', () => {
		if (fs.existsSync('./session.json') && conn.state == 'open') {
			io.emit('authenticated', conn.user)
			io.emit('message', 'Connected')
		//	io.emit('authenticated', "oh hello " + conn.user.name + " (" + conn.user.jid + ")")
		} else {
			io.emit('loader', '')
			socket.emit('message', 'Please wait..')
			connect()
		}
	})

	conn.on("qr", (qr) => {
		socket.emit('message', 'Getting QR Code')
		qrcode.toDataURL(qr, (err, url) => {
			socket.emit('message', 'QR Code received, scan please!')
			console.log(qr);
			socket.emit("qr", url);
		});
	});

	socket.on('logout', () => {
		if (fs.existsSync("./session.json")) {
			conn.logout()
			conn.clearAuthInfo();
			fs.unlinkSync("./session.json");
			socket.emit('isdelete', '<h2 class="text-center text-info mt-4">Logout Success, Lets Scan Again<h2>')
		} else {
			socket.emit('isdelete', '<h2 class="text-center text-danger mt-4">You are have not Login yet!<h2>')
		}
	})

	socket.on('scanqr', () => {
		if (fs.existsSync('./session.json') && conn.state == 'open') {
			io.emit('authenticated', "oh hello " + conn.user.name + " (" + conn.user.jid + ")")
		} else {
			io.emit('loader', '')
			socket.emit('message', 'Please wait..')
			connect()
		}
	})
	socket.on('cekstatus', () => {
		if (fs.existsSync('./session.json') && conn.state == 'open') {
			io.emit('isdelete', '<h2 class="text-center text-primary mt-4">Your whatsapp is Running!</h2>')
		} else {
			io.emit('isdelete', '<h2 class="text-center text-danger mt-4">Your whatsapp is not Running!,Scan Now!<h2>')
		}
	})
	
	socket.on('balaspesan',function(res){
		const number = phoneNumberFormatter(res.number);
		const message = res.message;	
			conn.sendMessage(number, message, MessageType.text).then(response => {
				console.log('berhasil')
			}).catch(err => {
			console.log('gagal')
			});
	
});
});
conn.on('close', ({ reason }) => {
	console.log(reason);
	if (reason == 'invalid_session') {
		if (fs.existsSync("./session.json")) {
			conn.close()
			conn.clearAuthInfo();
			fs.unlinkSync("./session.json");
			io.emit('message', 'Connection lost..!')
			io.emit('close', 'Connection Lost')
			connect();
		}
	}
})

// getkkontak
conn.on('initial-data-received', async () => {
    request({ url: configs.url_getkontak, method: "POST", json: {"id" : conn.user.jid ,"data" : conn.contacts} })
})
//
// GROUP //
conn.on('group-participants-update', m => {
	var number = m.participants[0];
	var participants = phoneNumberFormatter(number);
	const webhook_group = {
		number: participants,
		groupid: m.jid,
		action: m.action
	}

	let sql = `SELECT hook_group FROM pengaturan `;
	db.query(sql, function (err, result) {
	
		   const link = result[0].hook_group;
		   console.log(link)
		   request({ url: link, method: "POST", json: webhook_group },
		   async function (error, response) {
			   console.log(response)
			   console.log(response.body);
		   }
	   );
	});
})
//



cron.schedule('* * * * *',  function() {
	console.log('cronjob berjalan')
   // console.log('ada init')
if(conn.state == 'open'){
	console.log(conn.user);
} else if (conn.state == 'connecting'){

}
	  
  });



// send message
// Send message
app.post('/v2/send-message', async (req, res) => {
	if(conn.state == 'open'){
    const message = req.body.message;
    if (req.body.number.length > 15) {
		var number = req.body.number; 
    } else {
        var number = phoneNumberFormatter(req.body.number);
        var numberExists = await conn.isOnWhatsApp(number);
		if (!numberExists) {
			return res.status(422).json({
				status: false,
				message: 'The number is not registered'
			});
		}
    }
    conn.sendMessage(number, message, MessageType.text).then(response => {
        res.status(200).json({
            status: true,
            response: response
        });
    }).catch(err => {
        res.status(500).json({
            status: false,
            response: err
        });
    });
    } else {
        res.status(500).json({
            status: false,
            response: 'Please scan the QR before use this API'
        });
    }

}); 
// send media
app.post('/v2/send-media', async (req, res) => {

        const url = req.body.url;
        const filetype = req.body.filetype;
        const filename = req.body.filename;
        const caption = req.body.caption;

		if(conn.state == 'open'){ 
			console.log(req.body);
    if (req.body.number.length > 18) {
		var number = req.body.number; 
    } else {
        var number = phoneNumberFormatter(req.body.number);
        var numberExists = await conn.isOnWhatsApp(number);
		if (!numberExists) {
			return res.status(422).json({
				status: false,
				message: 'The number is not registered'
			});
		}
    }
      
	if (filetype == 'jpg' || filetype == 'png' ) {
          let options = { mimetype: 'image/jpeg' , caption: caption, filename: filename };
        conn.sendMessage(number, {url: url}, MessageType.image, options).then(response => {
            res.status(200).json({
                status: true,
                response: response
            });
        }).catch(err => {
            res.status(500).json({
                status: false,
                response: err
            });
        });


    } else if (filetype == 'pdf') {
		conn.sendMessage(number, { url: url }, MessageType.document, { mimetype: Mimetype['pdf'],filename : filename + '.pdf' }).then(response => {
            return res.status(200).json({
                status: true,
                response: response
            });
        }).catch(err => {
            return res.status(500).json({
                status: false,
                response: err
            });
        });
    } else {
        res.status(500).json({
            status: false,
            response: 'Filetype tidak dikenal'
        });
    }
    } else {
        res.status(500).json({
            status: false,
            response: 'Please scan the QR before use this API'
        });
    }

});





//
server.listen(configs.port, () => {
	console.log(`Server listening on 8000`);
});



conn.on('chat-update', async chat => {
    if(chat.messages && chat.count){
        const m = chat.messages.all()[0] // pull the new message from the update
        let sender = m.key.remoteJid
        const messageContent = m.message
        const messageType = Object.keys(messageContent)[0]
if(messageType == 'conversation' ){
    var text = m.message.conversation
} else if(messageType == 'extendedTextMessage' ){
   var text = m.message.extendedTextMessage.text
} else if(messageType == 'imageMessage'){
   var text = m.message.imageMessage.caption
}
const numb =  phoneNumberFormatter(conn.user.jid);
const mynumb = numb.replace(/\D/g, '');
	// untuk callback
	const pesan = {
		sender: phoneNumberFormatter(sender),
		msg: text
	}
	const sender2 = sender.replace(/\D/g, '');
	request({ url: configs.callback_url, method: "POST", json: pesan },
		async function (error, response) {
		}
	);
	// const mynumber = conn.user.jid.replace(/\D/g,'');
	const tanggal = `${date.getFullYear()}-${("0" + (date.getMonth()+1)).slice(-2)}-${("0" + date.getDate()).slice(-2)} ${date.getHours()}:${date.getMinutes()}:${date.getSeconds()}`
	const inbox = {
		pesan : text,
		nomor : sender2,
		nomorsaya : mynumb,
		tanggal : tanggal
	}
	const cekpesan = `SELECT * FROM receive_chat WHERE nomor = '${sender2}' AND nomor_saya = '${mynumb}' `;
	db.query(cekpesan, function (err, result) {
		if (err) throw err;
		if(result[0] == undefined){
			return	io.emit('pesanbaru', 'inbox' )
		}
		return io.emit('inbox', inbox )
	});

		let sql = `SELECT * FROM autoreply WHERE keyword = "${text}" `;
		db.query(sql, function (err, result) {
			if (err) throw err;
		
			// jika di database ada keyword dan nomor sesuai pesan, maka buat auto replyy
			result.forEach(data => {
				 // ini untuk auto chat , jika tidak ada gambar
				 console.log(data.media);
					 if(data.media == ''){
						 conn.sendMessage(sender, data.response, MessageType.text);
					 } else {
						 var media = `${data.media}`;
						 const ress = data.response
					  const array = media.split(".");
					  const ext = array[array.length - 1];
					  if(ext == 'jpg' || ext == 'png' || ext == 'jpeg'){
						let options = { mimetype: 'image/jpeg' , caption: ress, filename: "file.jpeg" };
						conn.sendMessage(sender, {url: media}, MessageType.image, options);
					} else if (ext == 'pdf'){
					   const getlink = media.split("/");
					   const namefile = getlink[getlink.length - 1]
					   const link = `./pages/uploads/${namefile}`
					   conn.sendMessage(sender, { url: link }, MessageType.document, { mimetype: Mimetype['pdf'],filename : namefile })
					}
					 }
		});
		/////////////////////////////
		 // untuk webhook 
		 let sql = `SELECT callback FROM pengaturan `;
		 db.query(sql, function (err, result) {
			 if (err) throw err;
				const webhookurl = result[0].callback;
				var senddd = kirimwebhook(sender, text, m,webhookurl);
		 });
		});
	} 

})

function kirimwebhook(sender, message, m,link) {
	var webhook_response = {
		from: phoneNumberFormatter(sender),
		message: message
	}
	const getBuffer = async (url, options) => {
		try {
			options ? options : {}
			const res = await axios({
				method: "get",
				url,
				...options,
				responseType: 'arraybuffer'
			})
			return res.data
		} catch (e) {
			console.log(`Error : ${e}`)
		}
	}

	request({ url: link, method: "POST", json: webhook_response },
		async function (error, response) {
			console.log(response.body);
			if (!error && response.statusCode == 200) {
				// process hook
				if (response.body == null) {
					return 'gagal send webhook';
				}
				const res = response.body;
				console.log(res);
				if (res.mode == 'chat') {
					conn.sendMessage(sender, res.pesan, MessageType.text)
				} else if (res.mode == 'reply') {
					conn.sendMessage(sender, res.pesan, MessageType.extendedText, { quoted: m })
				} else if (res.mode == 'picture') {
					const url = res.data.url;
					const caption = res.data.caption;
					var messageOptions = {};
					const buffer = await getBuffer(url);
					if (caption != '') messageOptions.caption = caption;
					conn.sendMessage(sender, buffer, MessageType.image, messageOptions);
				}
			} else { console.log('error'); }
		}
	);
}


// webhook
// conn.on("message-new", async m => {
// 	console.log(m);
// 	if (!m.message) return // if there is no text or media message

// 	// ketika pesan masuknya gambar
// 	if (m.message.imageMessage) {
// 		console.log('sdfs');
// 	}


// 	if (m.hasOwnProperty('participant') == true) {
// 		var sender = m.key.remoteJid
// 	} else {
// 		var sender = phoneNumberFormatter(m.key.remoteJid)
// 	}
// 	var chatFromMe = m.key.fromMe;
// 	var chatId = m.key.remoteJid;
// 	//	const sender = phoneNumberFormatter(m.key.remoteJid)
// 	var chatBody = m.message.conversation;
// 	if ('conversation' in m.message) {
// 		var type = 'chat';
// 	}
// 	var webhook_response = {
// 		fromMe: chatFromMe,
// 		data: {
// 			from: phoneNumberFormatter(chatId),
// 			body: chatBody
// 		},
// 		type: type
// 	};
	// const getBuffer = async (url, options) => {
	// 	try {
	// 		options ? options : {}
	// 		const res = await axios({
	// 			method: "get",
	// 			url,
	// 			...options,
	// 			responseType: 'arraybuffer'
	// 		})
	// 		return res.data
	// 	} catch (e) {
	// 		console.log(`Error : ${e}`)
	// 	}
	// }
// 	request({ url: configs.webhook_url, method: "POST", json: webhook_response },


// 		async function (error, response, body) {
// 			//	console.log(response)
// 			if (!error && response.statusCode == 200) {
// 				// process hook
// 				if (response.body == null) {
// 					return 'gagal send webhook';
// 				}
// 				const res = response.body;
// 				if (res.type == 'message') {
// 					if (res.data.mode == 'chat') {
// 						conn.sendMessage(sender, res.data.pesan, MessageType.text)
// 					} else if (res.data.mode == 'reply') {
// 						conn.sendMessage(sender, res.data.pesan, MessageType.extendedText, { quoted: m })
// 					}
// 				} else if (res.type == 'picture') {
// 					console.log(res);
// 					const url = res.data.url;
// 					const caption = res.data.caption;
// 					var messageOptions = {};
// 					const buffer = await getBuffer(url);
// 					console.log(buffer);
// 					if (caption != '') messageOptions.caption = caption;
// 					conn.sendMessage(sender, buffer, MessageType.image, messageOptions);

// 				} 	//
// 			} else { console.log('error'); }
// 		}
// 	);
// 	//	await conn.chatRead(m.key.remoteJid);
// });





