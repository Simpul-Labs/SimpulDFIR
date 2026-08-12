package metrics

import (
	"bufio"
	"bytes"
	"os/exec"
	"strings"
)

type Connection struct {
	Proto        string `json:"proto"`
	LocalAddress string `json:"local_address"`
	State        string `json:"state"`
	PIDProgram   string `json:"pid_program"`
}

// GetActiveConnections executes `ss -tunlp` to get active listening and established connections
func GetActiveConnections() []Connection {
	var connections []Connection

	cmd := exec.Command("ss", "-tunlp")
	var out bytes.Buffer
	cmd.Stdout = &out
	err := cmd.Run()
	if err != nil {
		return connections
	}

	scanner := bufio.NewScanner(&out)
	// Skip header
	if scanner.Scan() {
		// Header line, do nothing
	}

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) >= 6 {
			proto := fields[0] // tcp/udp
			state := fields[1] // LISTEN, ESTAB, UNCONN
			localAddr := fields[4] // 0.0.0.0:80

			// Find pid/program info in the last field (e.g., users:(("apache2",pid=123,fd=3)))
			pidProgram := "-"
			for _, f := range fields[5:] {
				if strings.Contains(f, "users:(") {
					pidProgram = extractProgramInfo(f)
					break
				}
			}

			connections = append(connections, Connection{
				Proto:        proto,
				LocalAddress: localAddr,
				State:        state,
				PIDProgram:   pidProgram,
			})
		}
	}

	return connections
}

func extractProgramInfo(s string) string {
	// users:(("apache2",pid=123,fd=3)) -> 123/apache2
	// Extremely simplistic parsing
	start := strings.Index(s, "((\"")
	if start == -1 {
		return "-"
	}
	s = s[start+3:]
	parts := strings.Split(s, "\"")
	if len(parts) >= 2 {
		prog := parts[0]
		pidStart := strings.Index(parts[1], "pid=")
		if pidStart != -1 {
			pidEnd := strings.Index(parts[1][pidStart:], ",")
			if pidEnd == -1 {
				pidEnd = strings.Index(parts[1][pidStart:], ")")
			}
			if pidEnd != -1 {
				pid := parts[1][pidStart+4 : pidStart+pidEnd]
				return pid + "/" + prog
			}
		}
		return prog
	}
	return "-"
}
