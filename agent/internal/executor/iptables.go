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
