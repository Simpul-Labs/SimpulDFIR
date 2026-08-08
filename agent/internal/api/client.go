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
