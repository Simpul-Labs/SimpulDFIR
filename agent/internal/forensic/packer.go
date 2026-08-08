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
