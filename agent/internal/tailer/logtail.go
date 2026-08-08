package tailer

import (
	"log"
	"os"
	"strings"

	"github.com/nxadm/tail"
)

// LogEvent represents a structured log entry sent to the API
type LogEvent struct {
	Timestamp   string `json:"timestamp"`
	Hostname    string `json:"hostname"`
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

			hostname, _ := os.Hostname()
			if hostname == "" {
				hostname = "unknown-server"
			}

			event := LogEvent{
				Timestamp:   line.Time.UTC().Format("2006-01-02T15:04:05Z"),
				Hostname:    hostname,
				SourceIP:    sourceIP,
				LogMessage:  line.Text,
				ThreatLevel: "CRITICAL",
			}

			outChan <- event
		}
	}
}
