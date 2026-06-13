FROM node:20-alpine

WORKDIR /app

COPY realtime/chat-server/package.json ./
RUN npm install --omit=dev

COPY realtime/chat-server/ ./

ENV NODE_ENV=production
EXPOSE 3001

CMD ["npm", "start"]
