// Biometric Bridge Service
// High-performance Go service for descriptor matching

package main

import (
	"encoding/json"
	"fmt"
	"math"
	"net/http"
)

type MatchRequest struct {
	DescriptorA []float64 `json:"a"`
	DescriptorB []float64 `json:"b"`
}

func calculateDistance(a, b []float64) float64 {
	sum := 0.0
	for i := range a {
		diff := a[i] - b[i]
		sum += diff * diff
	}
	return math.Sqrt(sum)
}

func matchHandler(w http.ResponseWriter, r *http.Request) {
	var req MatchRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	dist := calculateDistance(req.DescriptorA, req.DescriptorB)
	fmt.Fprintf(w, `{"distance": %f, "match": %v}`, dist, dist < 0.6)
}

func main() {
	http.HandleFunc("/match", matchHandler)
	fmt.Println("Biometric Bridge listening on :8081...")
	http.ListenAndServe(":8081", nil)
}
