package metrics

import (
	"bufio"
	"os"
	"runtime"
	"strconv"
	"strings"
	"syscall"
	"time"
)

var lastTotalCPU, lastIdleCPU uint64

func GetCPUUsage() float64 {
	file, err := os.Open("/proc/stat")
	if err != nil {
		return 0
	}
	defer file.Close()
	
	scanner := bufio.NewScanner(file)
	if scanner.Scan() {
		fields := strings.Fields(scanner.Text())
		if len(fields) > 7 && fields[0] == "cpu" {
			user, _ := strconv.ParseUint(fields[1], 10, 64)
			nice, _ := strconv.ParseUint(fields[2], 10, 64)
			sys, _ := strconv.ParseUint(fields[3], 10, 64)
			idle, _ := strconv.ParseUint(fields[4], 10, 64)
			iowait, _ := strconv.ParseUint(fields[5], 10, 64)
			irq, _ := strconv.ParseUint(fields[6], 10, 64)
			softirq, _ := strconv.ParseUint(fields[7], 10, 64)
			
			idleTime := idle + iowait
			nonIdleTime := user + nice + sys + irq + softirq
			totalTime := idleTime + nonIdleTime
			
			totalDiff := float64(totalTime - lastTotalCPU)
			idleDiff := float64(idleTime - lastIdleCPU)
			
			lastTotalCPU = totalTime
			lastIdleCPU = idleTime
			
			if totalDiff == 0 {
				return 0.0
			}
			
			cpuUsage := (totalDiff - idleDiff) / totalDiff * 100.0
			if cpuUsage < 0 {
				return 0.0
			}
			if cpuUsage > 100.0 {
				return 100.0
			}
			return cpuUsage
		}
	}
	return 0
}

func GetRAMUsage() float64 {
	file, err := os.Open("/proc/meminfo")
	if err != nil {
		return 0
	}
	defer file.Close()

	var memTotal, memAvailable float64
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		if strings.HasPrefix(line, "MemTotal:") {
			fields := strings.Fields(line)
			memTotal, _ = strconv.ParseFloat(fields[1], 64)
		} else if strings.HasPrefix(line, "MemAvailable:") {
			fields := strings.Fields(line)
			memAvailable, _ = strconv.ParseFloat(fields[1], 64)
		}
	}
	
	if memTotal == 0 {
		return 0
	}
	
	return ((memTotal - memAvailable) / memTotal) * 100.0
}

func GetCPUCount() int {
	return runtime.NumCPU()
}

func GetRAMUsedGB() float64 {
	file, err := os.Open("/proc/meminfo")
	if err != nil {
		return 0
	}
	defer file.Close()

	var memTotal, memAvailable float64
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		if strings.HasPrefix(line, "MemTotal:") {
			fields := strings.Fields(line)
			memTotal, _ = strconv.ParseFloat(fields[1], 64)
		} else if strings.HasPrefix(line, "MemAvailable:") {
			fields := strings.Fields(line)
			memAvailable, _ = strconv.ParseFloat(fields[1], 64)
		}
	}
	return (memTotal - memAvailable) / 1024.0 / 1024.0
}

func GetRAMTotalGB() float64 {
	file, err := os.Open("/proc/meminfo")
	if err != nil {
		return 0
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		if strings.HasPrefix(line, "MemTotal:") {
			fields := strings.Fields(line)
			memTotalKb, _ := strconv.ParseFloat(fields[1], 64)
			return memTotalKb / 1024.0 / 1024.0
		}
	}
	return 0
}

func GetDiskUsage() float64 {
	var stat syscall.Statfs_t
	err := syscall.Statfs("/", &stat)
	if err != nil {
		return 0.0
	}
	
	// stat.Blocks is total, stat.Bfree is free
	total := stat.Blocks * uint64(stat.Bsize)
	free := stat.Bfree * uint64(stat.Bsize)
	
	if total == 0 {
		return 0
	}
	
	return float64(total-free) / float64(total) * 100.0
}

var lastRx, lastTx uint64
var lastNetTime time.Time

func GetNetworkIO() (float64, float64) {
	file, err := os.Open("/proc/net/dev")
	if err != nil {
		return 0, 0
	}
	defer file.Close()

	var totalRx, totalTx uint64
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		if strings.Contains(line, "eth0:") || strings.Contains(line, "ens") || strings.Contains(line, "enp") {
			fields := strings.Fields(line)
			if len(fields) >= 10 {
				rx, _ := strconv.ParseUint(fields[1], 10, 64)
				tx, _ := strconv.ParseUint(fields[9], 10, 64)
				totalRx += rx
				totalTx += tx
			}
		}
	}
	
	now := time.Now()
	var rxMbps, txMbps float64
	
	if !lastNetTime.IsZero() {
		duration := now.Sub(lastNetTime).Seconds()
		if duration > 0 {
			rxMbps = float64(totalRx-lastRx) * 8 / 1000000.0 / duration
			txMbps = float64(totalTx-lastTx) * 8 / 1000000.0 / duration
		}
	}
	
	lastRx = totalRx
	lastTx = totalTx
	lastNetTime = now
	
	return rxMbps, txMbps
}
