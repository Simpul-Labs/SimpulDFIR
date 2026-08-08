package main

import (
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"
	
	"github.com/simpul-labs/simpul-dfir-agent/internal/api"
	"github.com/simpul-labs/simpul-dfir-agent/internal/tailer"
	// "github.com/simpul-labs/simpul-dfir-agent/internal/executor"
	// "github.com/simpul-labs/simpul-dfir-agent/internal/forensic"
)

func main() {
	log.Println("Starting Simpul DFIR Agent...")

	// 1. Initialize configuration
	authToken := os.Getenv("AGENT_AUTH_TOKEN")
	if authToken == "" {
		authToken = "dev-token-123" // Fallback for dev
	}
	
	masterURL := os.Getenv("MASTER_NODE_URL")
	if masterURL == "" {
		masterURL = api.MasterNodeURL // Fallback for dev
	}

	logFilePath := "/var/log/auth.log"
	
	// 2. Initialize Channels
	// We use a buffered channel to prevent blocking the tailer if API is slow
	logChan := make(chan tailer.LogEvent, 100)

	// 3. Start Goroutines
	// Start the tailer in the background
	go tailer.StartTailer(logFilePath, logChan)

	// Start the API client to listen to the channel and send data
	apiClient := api.NewAPIClient(masterURL, authToken)
	go apiClient.StartListening(logChan)

	// Send an initial registration heartbeat so the backend knows the agent is online
	hostname, _ := os.Hostname()
	if hostname == "" {
		hostname = "unknown-server"
	}
	logChan <- tailer.LogEvent{
		Timestamp:   time.Now().UTC().Format(time.RFC3339),
		Hostname:    hostname,
		SourceIP:    "0.0.0.0",
		LogMessage:  "Simpul DFIR Agent started successfully",
		ThreatLevel: "INFO",
	}

	// Example of using the forensic packer (mock)
	// result, err := forensic.PrepareArchive("/var/log/auth.log.1")
	// if err == nil {
	// 	log.Printf("Prepared %s with SHA256: %s", result.FilePath, result.SHA256)
	// }

	// Example of blocking an IP (mock)
	// err = executor.BlockIP("114.119.160.20")
	// if err != nil {
	// 	log.Printf("Error blocking IP: %v", err)
	// }

	// 4. Wait for OS interrupt to gracefully shut down
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
	
	log.Println("Simpul DFIR Agent is running. Press Ctrl+C to stop.")
	
	// Block until a signal is received
	sig := <-sigChan
	log.Printf("Received signal: %v. Shutting down gracefully...", sig)
	
	// Cleanup logic could go here
	close(logChan)
	log.Println("Agent stopped.")
}
