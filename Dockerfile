# Stage 1: Development
FROM node:20-alpine AS development

WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies (including devDependencies)
RUN npm ci

# Copy the rest of the application code
COPY . .

# Expose the Vite dev port
EXPOSE 5173

# Start Vite in host mode
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]


# Stage 2: Build the React application
FROM node:20-alpine AS build-stage

WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy the rest of the application code
COPY . .

# Build the application
RUN npm run build


# Stage 3: Serve the application with Nginx
FROM nginx:stable-alpine AS production-stage

# Copy the build output to the Nginx html directory
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Copy a custom nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Sur un serveur au umask strict (027), les fichiers du clone sont en 600 :
# Nginx ne pourrait pas les lire (404 / 403). On les rend lisibles par tous.
RUN chmod -R a+rX /usr/share/nginx/html /etc/nginx/conf.d

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
