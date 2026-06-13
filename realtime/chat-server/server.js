/**
 * Socket.IO chat server for Yii2 messaging.
 * Start: npm install && npm start
 */
const express = require('express');
const http = require('http');
const cors = require('cors');
const { Server } = require('socket.io');

const PORT = process.env.PORT || process.env.CHAT_PORT || 3001;
const app = express();
app.use(cors());
app.use(express.json({ limit: '1mb' }));

const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: '*', methods: ['GET', 'POST'] },
});

io.on('connection', (socket) => {
  const userId = socket.handshake.query.userId;
  if (userId) {
    socket.join('user:' + userId);
  }

  socket.on('join_conversation', (conversationId) => {
    if (conversationId) {
      socket.join('conversation:' + conversationId);
    }
  });

  socket.on('leave_conversation', (conversationId) => {
    if (conversationId) {
      socket.leave('conversation:' + conversationId);
    }
  });

  socket.on('disconnect', () => {});
});

app.post('/broadcast', (req, res) => {
  const { room, event, payload } = req.body || {};
  if (!room || !event) {
    return res.status(400).json({ ok: false });
  }
  io.to(room).emit(event, payload || {});
  res.json({ ok: true });
});

app.get('/health', (req, res) => {
  res.json({ ok: true });
});

server.listen(PORT, () => {
  console.log('Chat server listening on port ' + PORT);
});
