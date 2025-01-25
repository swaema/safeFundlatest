<!-- Payment Details Modal -->
<div class="modal fade modal-custom" id="paymentModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice me-2"></i>Loan Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <?php 
                $percent = Loan::calculatePercent($loan['id']);
                $date = new DateTime($loan['Accepted_Date']);
                $enddate = clone $date;
                $enddate->modify('+' . $loan['noOfInstallments'] . ' months');
                $installment = LoanInstallments::InstallmentAmountbyLoanId($loan['id']);
                
                // Calculate remaining balance
                $remainingBalance = $totalAmount - ($installment['total_paid'] ?? 0);
                ?>

                <!-- Progress Section -->
                <div class="info-card mb-4">
                    <h6 class="text-primary mb-3">Payment Progress</h6>
                    <div class="progress custom-progress mb-2">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $percent ?>%" 
                             aria-valuenow="<?php echo $percent ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>Total Progress</small>
                        <small class="fw-bold"><?php echo number_format($percent, 2) ?>%</small>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Timeline Section -->
                    <div class="col-md-6">
                        <div class="info-card h-100">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Loan Timeline
                            </h6>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Start Date</span>
                                <strong><?php echo $date->format('M d, Y') ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>End Date</span>
                                <strong><?php echo $enddate->format('M d, Y') ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Term Length</span>
                                <strong><?php echo $loan['noOfInstallments'] ?> months</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details Section -->
                    <div class="col-md-6">
                        <div class="info-card h-100">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-dollar-sign me-2"></i>Payment Details
                            </h6>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Principal Amount</span>
                                <strong>$<?php echo number_format($loan['loanAmount'], 2) ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Interest Rate</span>
                                <strong><?php echo $loan['interstRate'] ?>%</strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Monthly Payment</span>
                                <strong>$<?php echo number_format($loan['InstallmentAmount'], 2) ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Total Repayment</span>
                                <strong>$<?php echo number_format($totalAmount, 2) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Section -->
                    <div class="col-12">
                        <div class="info-card">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-chart-pie me-2"></i>Payment Status
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span>Remaining Balance</span>
                                        <strong class="d-block mt-1 text-danger">
                                            $<?php echo number_format($remainingBalance, 2) ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span>Amount Paid</span>
                                        <strong class="d-block mt-1 text-success">
                                            $<?php echo number_format($installment['total_paid'] ?? 0, 2) ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Payment Due Section -->
                <?php
                try {
                    $dueDate = Loan::calculateInstallmentDate($installment['last_payment_date'] ?? null, $loan['Accepted_Date']);
                    $nextDueDate = $dueDate['date'];
                    $remarks = $dueDate['remarks'];
                ?>
                    <div class="alert <?php echo ($remarks === 'Date is passed') ? 'alert-danger' : 'alert-success'; ?> mt-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-check me-2"></i>
                            <div>
                                <strong>Next Payment Due:</strong> <?php echo $nextDueDate; ?>
                                <span class="ms-2">(<?php echo $remarks; ?>)</span>
                            </div>
                        </div>
                    </div>
                <?php
                } catch (Exception $e) {
                ?>
                    <div class="alert alert-danger mt-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                    </div>
                <?php
                }
                ?>
            </div>

            <!-- Modal Footer with Original Payment Form -->
            <div class="modal-footer">
                <?php if ($remainingBalance > 0): ?>
                    <form action="" method="post" class="me-auto">
                        <?php
                        // Original payment calculations
                        $loaninfo = Loan::getLoanById($loan['id']);
                        $totalloan = $loaninfo['TotalLoan'];
                        $installamentamount = $loaninfo['InstallmentAmount'];
                        $loanamount = $loaninfo['loanAmount'];
                        $interestwithadmin = $totalloan - $loanamount;
                        $noofinstallements = $loaninfo['noOfInstallments'];
                        $successfee = $loanamount * 0.02;
                        $interest = round(($interestwithadmin - $successfee) / $noofinstallements, 2);
                        $principal = round($loanamount / $noofinstallements, 2);
                        $monthlyInterest = $interest;
                        $monthlyPrincipal = $principal;
                        $adminfee = $installamentamount - $principal - $interest;
                        ?>
                        
                        <input type="hidden" name="principal" value="<?php echo ceil($monthlyPrincipal); ?>">
                        <input type="hidden" name="interest" value="<?php echo floor($monthlyInterest); ?>">
                        <input type="hidden" name="loanId" value="<?php echo htmlspecialchars($loan['id']) ?>">
                        <input type="hidden" name="payamount" value="<?php echo htmlspecialchars($installamentamount) ?>">
                        <input type="hidden" name="interstRate" value="<?php echo htmlspecialchars($loan['interstRate']) ?>">
                        
                        <button type="submit" name="payIns" class="payment-btn">
                            <i class="fas fa-credit-card me-2"></i>Make Payment
                        </button>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>