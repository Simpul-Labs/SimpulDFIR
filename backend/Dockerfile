# Stage 1: Build the Go agent
FROM golang:1.22 AS agent-builder
WORKDIR /src
COPY agent/ .
RUN go mod tidy
RUN CGO_ENABLED=0 GOOS=linux go build -o simpul-agent cmd/agent/main.go

# Stage 2: Build the Python backend
FROM python:3.11-slim
WORKDIR /app

# Install Python dependencies
COPY backend/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source code
COPY backend/ .

# Copy the compiled agent binary from Stage 1 into the static folder
RUN mkdir -p /app/static
COPY --from=agent-builder /src/simpul-agent /app/static/simpul-agent

# Run the application
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
