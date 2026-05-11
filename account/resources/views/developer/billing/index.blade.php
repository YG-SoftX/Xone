@extends('developer.layouts.app')

@section('title', 'Billing')
@section('page-title', 'Billing & Invoices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Billing Overview</h1>
            <p class="text-gray-600 mt-1">Manage payment methods, invoices, and usage estimates</p>
        </div>
        <button onclick="openAddPaymentModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i>Add Payment Method
        </button>
    </div>

    <!-- Account Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-white text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1">${{ $billing['current_balance'] ?? '0.00' }}</h3>
            <p class="text-blue-100">Current Balance</p>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-receipt text-white text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1">${{ $billing['total_spent'] ?? '0.00' }}</h3>
            <p class="text-purple-100">Total Spent</p>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-1" id="estimatedCharges">$0.00</h3>
            <p class="text-green-100">Estimated This Month</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b">
            <nav class="flex">
                <button onclick="switchTab('invoices')" id="tab-invoices" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">
                    <i class="fas fa-file-invoice mr-2"></i>Invoices
                </button>
                <button onclick="switchTab('payment-methods')" id="tab-payment-methods" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-credit-card mr-2"></i>Payment Methods
                </button>
                <button onclick="switchTab('estimate')" id="tab-estimate" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-calculator mr-2"></i>Usage Estimate
                </button>
            </nav>
        </div>

        <!-- Invoices Tab -->
        <div id="content-invoices" class="tab-content p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTable" class="divide-y divide-gray-100">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium">{{ $invoice['invoice_number'] }}</td>
                                <td class="px-6 py-4 text-sm">
                                    {{ \Carbon\Carbon::parse($invoice['period_start'])->format('M d') }} - {{ \Carbon\Carbon::parse($invoice['period_end'])->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 font-semibold">${{ $invoice['formatted_total'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs {{ $invoice['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($invoice['is_overdue'] ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ ucfirst($invoice['status']) }}{{ $invoice['is_overdue'] ? ' (Overdue)' : '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($invoice['due_at'])->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    @if($invoice['status'] !== 'paid')
                                        <button onclick="payInvoice({{ $invoice['id'] }})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                            Pay Now
                                        </button>
                                    @else
                                        <button class="px-3 py-1 border rounded text-sm hover:bg-gray-50">
                                            <i class="fas fa-download mr-1"></i>PDF
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No invoices yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Methods Tab -->
        <div id="content-payment-methods" class="tab-content p-6 hidden">
            <div id="paymentMethodsList" class="space-y-4">
                @forelse($billing['payment_methods'] ?? [] as $method)
                    <div class="border rounded-lg p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-{{ $method['type'] === 'credit_card' ? 'credit-card' : ($method['type'] === 'bank_account' ? 'university' : 'paypal') }} text-gray-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 capitalize">{{ str_replace('_', ' ', $method['type']) }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ $method['brand'] ?? '' }} {{ $method['last_four'] ? '•••• ' . $method['last_four'] : '' }}
                                </p>
                                <p class="text-xs text-gray-400">Added {{ \Carbon\Carbon::parse($method['added_at'])->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($method['is_primary'])
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Primary</span>
                            @endif
                            <button onclick="removePaymentMethod('{{ $method['id'] }}')" class="px-3 py-1 border border-red-300 text-red-600 rounded hover:bg-red-50 text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-credit-card text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-600 mb-4">No payment methods added</p>
                        <button onclick="openAddPaymentModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Add Payment Method
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Usage Estimate Tab -->
        <div id="content-estimate" class="tab-content p-6 hidden">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-blue-700 text-sm"><strong>Note:</strong> This is an estimate based on current month usage. Final invoice will be generated on the 1st of next month.</p>
            </div>
            
            <div id="estimateDetails" class="space-y-4">
                <div class="text-center py-12">
                    <button onclick="loadEstimate()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Calculate Estimate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Method Modal -->
<div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Add Payment Method</h3>
            <button onclick="closeAddPaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="addPaymentForm" class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type *</label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="credit_card">Credit/Debit Card</option>
                    <option value="bank_account">Bank Account</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                <p class="text-yellow-700 text-sm"><strong>Integration Required:</strong> Connect with Stripe/PayPal/Razorpay to process payments securely.</p>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAddPaymentModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Payment Method</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName).classList.add('border-indigo-600', 'text-indigo-600');
}

async function loadEstimate() {
    try {
        const response = await fetch('/api/developer-console/billing/estimate');
        const data = await response.json();
        
        if (data.success) {
            displayEstimate(data.estimate);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function displayEstimate(estimate) {
    const container = document.getElementById('estimateDetails');
    
    document.getElementById('estimatedCharges').textContent = '$' + estimate.current_charges;
    
    let html = `
        <div class="bg-white border rounded-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h4 class="font-semibold text-gray-800">Current Month Charges</h4>
                <span class="text-2xl font-bold text-indigo-600">$${estimate.current_charges}</span>
            </div>
    `;
    
    if (estimate.breakdown.length > 0) {
        html += '<div class="space-y-3">';
        estimate.breakdown.forEach(item => {
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="font-medium">${item.project}</span>
                    <span class="font-semibold">$${item.charges}</span>
                </div>
            `;
        });
        html += '</div>';
    } else {
        html += '<p class="text-gray-500 text-center py-4">No charges this month</p>';
    }
    
    html += `
            <div class="mt-6 pt-6 border-t">
                <p class="text-sm text-gray-600">${estimate.note}</p>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

async function payInvoice(invoiceId) {
    if (!confirm('Proceed with payment?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/billing/invoices/${invoiceId}/pay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ payment_method_id: 'default' })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Payment successful!');
            window.location.reload();
        } else {
            alert(result.error || 'Payment failed');
        }
    } catch (error) {
        alert('Payment processing failed');
    }
}

function openAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.remove('hidden');
}

function closeAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.add('hidden');
    document.getElementById('addPaymentForm').reset();
}

document.getElementById('addPaymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // In production, integrate with Stripe Elements or similar
    alert('Payment gateway integration required. Configure Stripe/PayPal/Razorpay in .env');
});

async function removePaymentMethod(methodId) {
    if (!confirm('Remove this payment method?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/billing/payment-methods/${methodId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        }
    } catch (error) {
        alert('Failed to remove payment method');
    }
}

// Load estimate on page load
document.addEventListener('DOMContentLoaded', function() {
    loadEstimate();
});
</script>
@endpush
@endsection
