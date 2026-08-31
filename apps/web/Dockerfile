FROM node:26.8.1-bookworm AS development

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

CMD ["npm", "start", "--", "--host", "0.0.0.0", "--poll", "1000"]
