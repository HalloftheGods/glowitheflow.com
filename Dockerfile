FROM node:22

# Enable corepack and use pnpm
RUN corepack enable

WORKDIR /app

# In development, dependencies and code will be mounted from the host OS
# However, we install them locally inside the image too, as a fallback baseline
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY . .

EXPOSE 3000

# Start Nuxt DEV server bound to all network interfaces
CMD ["pnpm", "dev", "--host", "0.0.0.0"]
