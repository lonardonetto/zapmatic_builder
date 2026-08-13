package bulk

// AccountRotator implements round-robin rotation across accounts.
type AccountRotator struct {
	accounts []int
	index    int
}

// NewAccountRotator creates a rotator from the campaign account list.
func NewAccountRotator(accounts []int) *AccountRotator {
	return &AccountRotator{
		accounts: accounts,
		index:    0,
	}
}

// NewAccountRotatorWithIndex creates a rotator starting at a specific index.
func NewAccountRotatorWithIndex(accounts []int, startIndex int) *AccountRotator {
	return &AccountRotator{
		accounts: accounts,
		index:    startIndex % max(1, len(accounts)),
	}
}

// Next returns the next account ID and advances the pointer.
// Returns 0 if the account list is empty.
func (r *AccountRotator) Next() int {
	if len(r.accounts) == 0 {
		return 0
	}
	account := r.accounts[r.index]
	r.index = (r.index + 1) % len(r.accounts)
	return account
}

// Current returns the current account without advancing.
func (r *AccountRotator) Current() int {
	if len(r.accounts) == 0 {
		return 0
	}
	return r.accounts[r.index]
}

// Index returns the current index position.
func (r *AccountRotator) Index() int {
	return r.index
}

// SetIndex sets the index to a specific position.
func (r *AccountRotator) SetIndex(idx int) {
	if len(r.accounts) > 0 {
		r.index = idx % len(r.accounts)
	} else {
		r.index = 0
	}
}

// Len returns the number of accounts.
func (r *AccountRotator) Len() int {
	return len(r.accounts)
}

// HasMore returns true if there are accounts available.
func (r *AccountRotator) HasMore() bool {
	return len(r.accounts) > 0
}

// Reset resets the pointer to the beginning.
func (r *AccountRotator) Reset() {
	r.index = 0
}
