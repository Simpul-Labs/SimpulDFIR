import os
from pathlib import Path

base_dir = Path("d:/Simpul-DFIR/agent")
files = {
    "go.mod": """\
module github.com/simpul-labs/simpul-dfir-agent

go 1.21

require (
	github.com/nxadm/tail v1.4.11
)
""",
    "internal/tailer/logtail.go": """\
package tailer

import (
	"log"
	"strings"

	"github.com/nxadm/tail"
)

// LogEvent represents a structured log entry sent to the API
type LogEvent struct {
	Timestamp   string `json:"timestamp"`
	SourceIP    string `json:"source_ip"`
	LogMessage  string `json:"log_message"`
	ThreatLevel string `json:"threat_level"`
}

// StartTailer tails a file and sends matching lines to a channel
func StartTailer(filePath string, outChan chan<- LogEvent) {
	t, err := tail.TailFile(filePath, tail.Config{
		Follow: true,
		ReOpen: true,
		MustExist: false,
	})
	if err != nil {
		log.Fatalf("Failed to tail file %s: %v", filePath, err)
	}

	log.Printf("Started tailing: %s", filePath)

	for line := range t.Lines {
		if line.Err != nil {
			log.Printf("Error reading line: %v", line.Err)
			continue
		}

		// Simple parsing logic looking for "Failed password"
		if strings.Contains(line.Text, "Failed password") {
			// Extract IP - basic logic for demonstration
			parts := strings.Split(line.Text, " ")
			var sourceIP string
			for i, part := range parts {
				if part == "from" && i+1 < len(parts) {
					sourceIP = parts[i+1]
					break
				}
			}

			event := LogEvent{
				Timestamp:   line.Time.UTC().Format("2006-01-02T15:04:05Z"),
				SourceIP:    sourceIP,
				LogMessage:  line.Text,
				ThreatLevel: "CRITICAL",
			}

			outChan <- event
		}
	}
}
""",
    "internal/api/client.go": """\
package api

import (
	"bytes"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"time"

	"github.com/simpul-labs/simpul-dfir-agent/internal/tailer"
)

const MasterNodeURL = "http://localhost:8000/api/v1/logs/push"

// APIClient handles communication with the Master Node
type APIClient struct {
	MasterURL  string
	AuthToken  string
	HttpClient *http.Client
}

// NewAPIClient creates a new client
func NewAPIClient(masterURL, authToken string) *APIClient {
	return &APIClient{
		MasterURL: masterURL,
		AuthToken: authToken,
		HttpClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}

// StartListening listens to the log channel and forwards logs to the Master Node
func (c *APIClient) StartListening(logChan <-chan tailer.LogEvent) {
	log.Println("API Client listening for logs...")
	for event := range logChan {
		// The API expects a list of logs
		payload := []tailer.LogEvent{event}
		
		err := c.pushLogs(payload)
		if err != nil {
			log.Printf("Failed to push log: %v", err)
		} else {
			log.Printf("Successfully pushed log from IP: %s", event.SourceIP)
		}
	}
}

func (c *APIClient) pushLogs(logs []tailer.LogEvent) error {
	jsonData, err := json.Marshal(logs)
	if err != nil {
		return err
	}

	req, err := http.NewRequest("POST", c.MasterURL, bytes.NewBuffer(jsonData))
	if err != nil {
		return err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", c.AuthToken))

	resp, err := c.HttpClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode > 299 {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	return nil
}
""",
    "internal/executor/iptables.go": """\
package executor

import (
	"fmt"
	"log"
	"os/exec"
)

// BlockIP executes an iptables system call to drop traffic from a target IP
func BlockIP(targetIP string) error {
	if targetIP == "" {
		return fmt.Errorf("targetIP cannot be empty")
	}

	log.Printf("Executing system call to block IP: %s", targetIP)

	// Command: iptables -A INPUT -s <target_ip> -j DROP
	cmd := exec.Command("iptables", "-A", "INPUT", "-s", targetIP, "-j", "DROP")
	
	// Execute the command and capture any error/output
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to execute iptables command: %v, output: %s", err, string(output))
	}

	log.Printf("Successfully blocked IP %s", targetIP)
	return nil
}
""",
    "internal/forensic/packer.go": """\
package forensic

import (
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"io"
	"os"
)

// PackResult holds the result of preparing a forensic archive
type PackResult struct {
	FilePath string
	SHA256   string
	FileSize int64
}

// PrepareArchive calculates the SHA-256 hash of a file and prepares it for upload
func PrepareArchive(filePath string) (*PackResult, error) {
	file, err := os.Open(filePath)
	if err != nil {
		return nil, fmt.Errorf("failed to open file %s: %v", filePath, err)
	}
	defer file.Close()

	stat, err := file.Stat()
	if err != nil {
		return nil, fmt.Errorf("failed to stat file %s: %v", filePath, err)
	}

	hash := sha256.New()
	if _, err := io.Copy(hash, file); err != nil {
		return nil, fmt.Errorf("failed to calculate hash: %v", err)
	}

	hashInBytes := hash.Sum(nil)
	sha256String := hex.EncodeToString(hashInBytes)

	return &PackResult{
		FilePath: filePath,
		SHA256:   sha256String,
		FileSize: stat.Size(),
	}, nil
}
""",
    "cmd/agent/main.go": """\
package main

import (
	"log"
	"os"
	"os/signal"
	"syscall"
	
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
"""
}

for rel_path, content in files.items():
    p = base_dir / rel_path
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content, encoding='utf-8')

print(f"Scaffolded {len(files)} files successfully at {base_dir}")
