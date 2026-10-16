# Setup
FROM node:22-alpine AS base
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

# Dev
FROM base AS development
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host"]

# Setup Prod
FROM base AS builder
COPY . .
RUN npm run build

# Run
FROM node:22-alpine AS runner
WORKDIR /app
RUN npm install -g serve
COPY --from=builder /app/dist ./dist
EXPOSE 5173
CMD ["serve", "-s", "dist", "-l", "5173"]