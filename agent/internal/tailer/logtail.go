package tailer

import (
	"log"
	"os"
	"regexp"
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

var ipRegex = regexp.MustCompile(`\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b`)

// StartTailer tails a file and sends matching lines to a channel
func StartTailer(filePath string, outChan chan<- LogEvent) {
	// Check if file exists before trying to tail
	if _, err := os.Stat(filePath); os.IsNotExist(err) {
		log.Printf("Log file does not exist, skipping: %s", filePath)
		return
	}

	t, err := tail.TailFile(filePath, tail.Config{
		Follow:    true,
		ReOpen:    true,
		MustExist: false,
	})
	if err != nil {
		log.Printf("Failed to tail file %s: %v", filePath, err)
		return
	}

	log.Printf("Started tailing: %s", filePath)

	hostname, _ := os.Hostname()
	if hostname == "" {
		hostname = "unknown-server"
	}

	for line := range t.Lines {
		if line.Err != nil {
			log.Printf("Error reading line in %s: %v", filePath, line.Err)
			continue
		}

		text := line.Text
		if strings.TrimSpace(text) == "" {
			continue
		}

		// Extract IP if present in log message
		ipMatch := ipRegex.FindString(text)
		sourceIP := "0.0.0.0"
		if ipMatch != "" {
			sourceIP = ipMatch
		}

		threatLevel := "INFO"
		lower := strings.ToLower(text)

		// Rule-based classification
		if strings.Contains(lower, "failed password") || strings.Contains(lower, "authentication failure") || strings.Contains(lower, "invalid user") {
			threatLevel = "CRITICAL"
		} else if strings.Contains(lower, "ufw block") || strings.Contains(lower, "iptables") || strings.Contains(lower, "port_scan") {
			threatLevel = "HIGH"
		} else if strings.Contains(lower, "error") || strings.Contains(lower, "denied") || strings.Contains(lower, "refused") {
			threatLevel = "MEDIUM"
		} else if strings.Contains(lower, "accepted password") || strings.Contains(lower, "session opened") {
			threatLevel = "LOW"
		}

		event := LogEvent{
			Timestamp:   line.Time.UTC().Format("2006-01-02T15:04:05Z"),
			Hostname:    hostname,
			SourceIP:    sourceIP,
			LogMessage:  text,
			ThreatLevel: threatLevel,
		}

		outChan <- event
	}
}
