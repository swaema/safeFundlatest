
const lendInterestRate = 0.0; 
let mode = "borrow"; 
let isLoaded = false;

const amountInput = document.getElementById('amount');
const amountRange = document.getElementById('amountRange');
const interestInput = document.getElementById('interestInput'); 
const termInput = document.getElementById('termInput'); 
const monthlyRepaymentSpan = document.getElementById('monthlyRepayment');
const totalRepaymentSpan = document.getElementById('totalRepayment');

const modeText = document.getElementById('modeText');
const repaymentMode = document.getElementById('repaymentMode');
const ctx = document.getElementById('loanChart').getContext('2d');

// Initialize the chart
const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Loan Amount', 'Interest', 'Success Fee'],
        datasets: [{
            data: [0, 0, 0],
            backgroundColor: ['#007bff', '#6c757d', '#ffc107'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Function to update the values
function updateValues() {
    const loanAmount = parseFloat(amountInput.value);
    const selectedInterestRate = parseFloat(interestInput.value) / 100 || 0.00; // Default to 8% if empty
    const selectedDuration = parseInt(termInput.value) || 36; // Default to 12 months if empty
    let interest, totalRepayment, monthlyRepayment;


    const successFee = loanAmount * 0.02; 
    

    if (mode === "borrow") {
        interest = loanAmount * selectedInterestRate * (selectedDuration / 12);
        totalRepayment = loanAmount + interest + successFee;
        monthlyRepayment = totalRepayment / selectedDuration;
    } 

    monthlyRepaymentSpan.textContent = monthlyRepayment.toFixed(2);
    totalRepaymentSpan.textContent = totalRepayment.toFixed(2);
    document.getElementById('totalloan').value=totalRepayment.toFixed(2);

    chart.data.labels = mode === "borrow" ? ['Loan Amount', 'Interest', 'Success Fee'] : ['Principal', 'Interest'];
    chart.data.datasets[0].data = mode === "borrow" ? [loanAmount, interest, successFee] : [loanAmount, interest];
    chart.update();
}

// Input field listeners
amountInput.addEventListener('input', () => {
    amountRange.value = amountInput.value;
    updateValues();
});

amountRange.addEventListener('input', () => {
    amountInput.value = amountRange.value;
    updateValues();
});

interestInput.addEventListener('input', updateValues);
termInput.addEventListener('input', updateValues);
updateValues();
