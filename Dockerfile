# Stage 1: Build Stage
FROM node:14-alpine AS build

# Set working directory inside the container
WORKDIR /app

# Copy application code inside image
COPY . .

# Install dependencies
RUN npm install

# Build the React application
RUN npm run build

# Stage 2: Production Stage
FROM nginx:alpine

# Remove the default Nginx configuration
RUN rm -rf /usr/share/nginx/html/*

# Copy custom Nginx configuration file
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy the build output from the previous stage to the Nginx html directory
COPY --from=build /app/build /usr/share/nginx/html

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]