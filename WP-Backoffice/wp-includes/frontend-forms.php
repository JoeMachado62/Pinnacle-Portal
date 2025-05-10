<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register shortcode and actions
 */
function paf_core_register_form_actions_and_shortcodes() {
    add_shortcode( 'paf_credit_application_form', 'paf_render_credit_application_form' );
    add_action( 'admin_post_nopriv_paf_submit_credit_app', 'paf_handle_credit_application_submission' );
    add_action( 'admin_post_paf_submit_credit_app', 'paf_handle_credit_application_submission' );
}

/**
 * Render Credit Application Form
 */
function paf_render_credit_application_form() {
    if ( ! is_user_logged_in() || ! current_user_can('paf_submit_credit_application') ) {
        return '<p class="paf-alert paf-alert-warning">You must be logged in as an authorized dealer to submit an application.</p>';
    }

    ob_start();
    ?>
    <div class="paf-credit-app-form-container form-container">
        <h1>Credit Application</h1>
        <form id="pafCreditApplicationForm" method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="paf_submit_credit_app">
            <?php wp_nonce_field( 'paf_submit_app_nonce_action', 'paf_app_nonce_field' ); ?>

            <!-- SECTION: Dealer Info -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-field">
                            <label for="dealerName">Dealer Name:</label>
                            <input type="text" id="dealerName" name="dealerName" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-field">
                            <label for="telephone">Tel:</label>
                            <input type="tel" id="telephone" name="telephone" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-field">
                            <label for="contact">Contact:</label>
                            <input type="text" id="contact" name="contact" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="notice">
                <strong>IMPORTANT APPLICANT INFORMATION:</strong> Federal Law requires financial institutions to obtain sufficient information to verify your identity. You may be asked several questions and to provide one or more forms of identification to fulfill this requirement. In some instances we may use outside sources to confirm the information. The information you provide is protected by our privacy policy and federal law.
            </div>

            <!-- Primary Borrower -->
            <div class="borrower-container" id="borrower1Container">
                <div class="borrower-header">Primary Borrower</div>

                <!-- Personal Information -->
                <div class="form-section form-section-alt borrower-personal-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_applicant">Applicant:</label>
                                <input type="text" id="b1_applicant" name="borrower1_applicant" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_dob">Date of birth:</label>
                                <input type="date" id="b1_dob" name="borrower1_dob" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_ssn">SSN:</label>
                                <input type="text" id="b1_ssn" name="borrower1_ssn" required data-paf-encrypt="true" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_driversLicense">Driver's License:</label>
                                <input type="text" id="b1_driversLicense" name="borrower1_driversLicense" data-paf-encrypt="true" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_homePhone">Home:</label>
                                <input type="tel" id="b1_homePhone" name="borrower1_homePhone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_workPhone">Work:</label>
                                <input type="tel" id="b1_workPhone" name="borrower1_workPhone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_cellPhone">Cell:</label>
                                <input type="tel" id="b1_cellPhone" name="borrower1_cellPhone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_email">E‑Mail:</label>
                                <input type="email" id="b1_email" name="borrower1_email">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="form-section borrower-address-section">
                    <h3>Address Information</h3>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label>Current address & Type:</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="borrower1_addressType" value="own"> Own</label>
                                    <label><input type="radio" name="borrower1_addressType" value="rent"> Rent</label>
                                    <label><input type="radio" name="borrower1_addressType" value="other"> Other</label>
                                </div>
                                <input type="text" id="b1_currentAddress" name="borrower1_currentAddress" placeholder="Address" style="margin-top: 10px;" required>
                            </div>
                        </div>
                        <div class="form-col" style="flex: 1;">
                            <div class="form-field">
                                <label for="b1_landlordInfo">Landlord/Mortgage Co. & Phone:</label>
                                <input type="text" id="b1_landlordInfo" name="borrower1_landlordInfo">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_currentAddressYears">Years:</label>
                                <input type="number" id="b1_currentAddressYears" name="borrower1_currentAddressYears" min="0" required>
                            </div>
                            <div class="form-field">
                                <label for="b1_currentAddressMonths">Months:</label>
                                <input type="number" id="b1_currentAddressMonths" name="borrower1_currentAddressMonths" min="0" max="11" required>
                            </div>
                        </div>
                    </div>

                    <!-- Previous address 1 -->
                    <div class="form-row hidden-section" data-address-level="1">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_previousAddress1">Previous address 1:</label>
                                <input type="text" id="b1_previousAddress1" name="borrower1_previousAddress1">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 1;">
                            <div class="form-field">
                                <label for="b1_prevAddress1LandlordInfo">Landlord/Mortgage Co. & Phone:</label>
                                <input type="text" id="b1_prevAddress1LandlordInfo" name="borrower1_prevAddress1LandlordInfo">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_prevAddress1Years">Years:</label>
                                <input type="number" id="b1_prevAddress1Years" name="borrower1_prevAddress1Years" min="0">
                            </div>
                            <div class="form-field">
                                <label for="b1_prevAddress1Months">Months:</label>
                                <input type="number" id="b1_prevAddress1Months" name="borrower1_prevAddress1Months" min="0" max="11">
                            </div>
                        </div>
                    </div>

                    <!-- Previous address 2 -->
                    <div class="form-row hidden-section" data-address-level="2">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_previousAddress2">Previous address 2:</label>
                                <input type="text" id="b1_previousAddress2" name="borrower1_previousAddress2">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 1;">
                            <div class="form-field">
                                <label for="b1_prevAddress2LandlordInfo">Landlord/Mortgage Co. & Phone:</label>
                                <input type="text" id="b1_prevAddress2LandlordInfo" name="borrower1_prevAddress2LandlordInfo">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_prevAddress2Years">Years:</label>
                                <input type="number" id="b1_prevAddress2Years" name="borrower1_prevAddress2Years" min="0">
                            </div>
                            <div class="form-field">
                                <label for="b1_prevAddress2Months">Months:</label>
                                <input type="number" id="b1_prevAddress2Months" name="borrower1_prevAddress2Months" min="0" max="11">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="form-section form-section-alt borrower-employment-section">
                    <h3>Employment Information</h3>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_currentEmployer">Current Employer:</label>
                                <input type="text" id="b1_currentEmployer" name="borrower1_currentEmployer">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_employerPhone">Phone:</label>
                                <input type="tel" id="b1_employerPhone" name="borrower1_employerPhone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_title">Title:</label>
                                <input type="text" id="b1_title" name="borrower1_title">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_employerAddress">Employer Address:</label>
                                <input type="text" id="b1_employerAddress" name="borrower1_employerAddress">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_income">Income:</label>
                                <input type="text" id="b1_income" name="borrower1_income" class="currency-input">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_employmentYears">Years:</label>
                                <input type="number" id="b1_employmentYears" name="borrower1_employmentYears" min="0">
                            </div>
                            <div class="form-field">
                                <label for="b1_employmentMonths">Months:</label>
                                <input type="number" id="b1_employmentMonths" name="borrower1_employmentMonths" min="0" max="11">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_otherIncome">Other Income:</label>
                                <input type="text" id="b1_otherIncome" name="borrower1_otherIncome" class="currency-input">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_incomeSource">Source (Alimony, child support or separate maintenance income need not be revealed if you do not wish):</label>
                                <input type="text" id="b1_incomeSource" name="borrower1_incomeSource">
                            </div>
                        </div>
                    </div>

                    <!-- Previous Employer 1 -->
                    <div class="form-row hidden-section" data-employment-level="1">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_previousEmployer1">Previous Employer 1:</label>
                                <input type="text" id="b1_previousEmployer1" name="borrower1_previousEmployer1">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer1Phone">Phone:</label>
                                <input type="tel" id="b1_prevEmployer1Phone" name="borrower1_prevEmployer1Phone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer1Title">Title:</label>
                                <input type="text" id="b1_prevEmployer1Title" name="borrower1_prevEmployer1Title">
                            </div>
                        </div>
                    </div>
                    <div class="form-row hidden-section" data-employment-level="1">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_prevEmployer1Address">Employer Address:</label>
                                <input type="text" id="b1_prevEmployer1Address" name="borrower1_prevEmployer1Address">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer1Income">Income:</label>
                                <input type="text" id="b1_prevEmployer1Income" name="borrower1_prevEmployer1Income" class="currency-input">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_prevEmployment1Years">Years:</label>
                                <input type="number" id="b1_prevEmployment1Years" name="borrower1_prevEmployment1Years" min="0">
                            </div>
                            <div class="form-field">
                                <label for="b1_prevEmployment1Months">Months:</label>
                                <input type="number" id="b1_prevEmployment1Months" name="borrower1_prevEmployment1Months" min="0" max="11">
                            </div>
                        </div>
                    </div>

                    <!-- Previous Employer 2 -->
                    <div class="form-row hidden-section" data-employment-level="2">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_previousEmployer2">Previous Employer 2:</label>
                                <input type="text" id="b1_previousEmployer2" name="borrower1_previousEmployer2">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer2Phone">Phone:</label>
                                <input type="tel" id="b1_prevEmployer2Phone" name="borrower1_prevEmployer2Phone">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer2Title">Title:</label>
                                <input type="text" id="b1_prevEmployer2Title" name="borrower1_prevEmployer2Title">
                            </div>
                        </div>
                    </div>
                    <div class="form-row hidden-section" data-employment-level="2">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_prevEmployer2Address">Employer Address:</label>
                                <input type="text" id="b1_prevEmployer2Address" name="borrower1_prevEmployer2Address">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_prevEmployer2Income">Income:</label>
                                <input type="text" id="b1_prevEmployer2Income" name="borrower1_prevEmployer2Income" class="currency-input">
                            </div>
                        </div>
                        <div class="form-col" style="flex: 0.5;">
                            <div class="form-field">
                                <label for="b1_prevEmployment2Years">Years:</label>
                                <input type="number" id="b1_prevEmployment2Years" name="borrower1_prevEmployment2Years" min="0">
                            </div>
                            <div class="form-field">
                                <label for="b1_prevEmployment2Months">Months:</label>
                                <input type="number" id="b1_prevEmployment2Months" name="borrower1_prevEmployment2Months" min="0" max="11">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="form-section borrower-financial-section">
                    <h3>Financial Information</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-field">
                                <label>Are you obliged to make Alimony, Support or Maintenance payments?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="borrower1_alimonyPayments" value="no" checked> No</label>
                                    <label><input type="radio" name="borrower1_alimonyPayments" value="yes"> Yes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label for="b1_alimonyRecipient">If yes, to (Name & Address):</label>
                                <input type="text" id="b1_alimonyRecipient" name="borrower1_alimonyRecipient">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_alimonyAmount">Amt. per month $:</label>
                                <input type="text" id="b1_alimonyAmount" name="borrower1_alimonyAmount" class="currency-input">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label>Are you a co-maker, endorser, or guarantor on any loan or contract?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="borrower1_coMaker" value="no" checked> No</label>
                                    <label><input type="radio" name="borrower1_coMaker" value="yes"> Yes</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_coMakerFor">If yes, for whom?</label>
                                <input type="text" id="b1_coMakerFor" name="borrower1_coMakerFor">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_coMakerToWhom">To whom?</label>
                                <input type="text" id="b1_coMakerToWhom" name="borrower1_coMakerToWhom">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label>Are there any unsatisfied judgments against you?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="borrower1_judgments" value="no" checked> No</label>
                                    <label><input type="radio" name="borrower1_judgments" value="yes"> Yes</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_judgmentToWhom">If yes, to whom owed?</label>
                                <input type="text" id="b1_judgmentToWhom" name="borrower1_judgmentToWhom">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_judgmentAmount">Amount $:</label>
                                <input type="text" id="b1_judgmentAmount" name="borrower1_judgmentAmount" class="currency-input">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="flex: 2;">
                            <div class="form-field">
                                <label>Have you been declared bankrupt in the last 10 years?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="borrower1_bankruptcy" value="no" checked> No</label>
                                    <label><input type="radio" name="borrower1_bankruptcy" value="yes"> Yes</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_bankruptcyWhere">If yes, where?</label>
                                <input type="text" id="b1_bankruptcyWhere" name="borrower1_bankruptcyWhere">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-field">
                                <label for="b1_bankruptcyYear">Year?</label>
                                <input type="text" id="b1_bankruptcyYear" name="borrower1_bankruptcyYear">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Co-Borrower Toggle -->
            <div class="add-borrower-container" id="toggleBorrowerButtonContainer">
                <button type="button" id="toggleBorrowerBtn" class="add-borrower-btn">Add Co-Borrower</button>
            </div>
            <div class="borrower-container" id="borrower2Container" style="display:none;"></div>

            <!-- Dealer Attestation -->
            <div class="form-section form-section-alt">
                <div class="attestation-area">
                    <p style="font-weight: 500; color: var(--primary-purple);">DEALER ATTESTATION</p>
                    <p>I, as an authorized representative of the dealership, certify that everything stated in this application is correct. I confirm that I have received authorization from the applicant to submit this application. The applicant understands that the Lender may check their credit and employment history and answer questions others may ask the Lender about the applicant's credit record with the Lender. The applicant also understands that they must update credit information at Lender's request if their financial condition changes.</p>
                    <label class="checkbox-label">
                        <input type="checkbox" id="dealerAttestation" name="dealerAttestation" required>
                        I agree to the above statements and confirm that I am authorized to submit this application on behalf of the applicant.
                    </label>
                    <div class="form-field" style="margin-top:15px;">
                        <label for="signatureDate">Date:</label>
                        <input type="date" id="signatureDate" name="signatureDate" required>
                    </div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="form-section">
                <h3>Vehicle Information</h3>
                <div class="vehicle-grid">
                    <div class="form-field">
                        <label for="vehicleYear">Year:</label>
                        <input type="text" id="vehicleYear" name="vehicleYear">      
                    </div>
                    <div class="form-field">
                        <label for="vehicleMakeModel">Make & Model:</label>
                        <input type="text" id="vehicleMakeModel" name="vehicleMakeModel">
                    </div>
                    <div class="form-field">
                        <label for="vehicleTrim">Trim:</label>
                        <input type="text" id="vehicleTrim" name="vehicleTrim">
                    </div>
                    <div class="form-field">
                        <label for="vehicleMileage">Mileage:</label>
                        <input type="text" id="vehicleMileage" name="vehicleMileage">
                    </div>
                    <div class="form-field">
                        <label for="vehicleVIN">VIN:</label>
                        <input type="text" id="vehicleVIN" name="vehicleVIN">
                    </div>
                </div>
            </div>

            <!-- Terms Section -->
            <div class="form-section form-section-alt">
                <h3>Terms</h3>
                <div class="terms-grid">
                    <div class="terms-item">
                        <label class="terms-label">a. Selling Price:</label>
                        <input type="text" id="sellingPrice" name="sellingPrice" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">b. Warranty:</label>
                        <input type="text" id="warranty" name="warranty" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">c. GAP:</label>
                        <input type="text" id="gap" name="gap" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">d. Other:</label>
                        <input type="text" id="other" name="other" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">e. Taxes:</label>
                        <div class="tax-rate-input">
                            <input type="text" id="taxRate" name="taxRate" placeholder="0.0%">
                            <input type="text" id="taxes" name="taxes" class="currency-input" placeholder="$0.00" readonly>
                        </div>
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">f. Doc Fees:</label>
                        <input type="text" id="docFees" name="docFees" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">g. Title/Lien/Reg Fees:</label>
                        <input type="text" id="titleFees" name="titleFees" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-total">l. Total Price: (Sum a:g)</label>
                        <input type="text" id="totalPrice" name="totalPrice" class="currency-input" readonly>
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">h. Total Cash Down:</label>
                        <input type="text" id="cashDown" name="cashDown" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">i. Trade Value:</label>
                        <input type="text" id="tradeValue" name="tradeValue" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">j. Trade Payoff:</label>
                        <input type="text" id="tradePayoff" name="tradePayoff" class="currency-input" placeholder="$0.00">
                    </div>
                    <div class="terms-item">
                        <label class="terms-label">k. Net Trade Value:</label>
                        <input type="text" id="netTradeValue" name="netTradeValue" class="currency-input" readonly>
                    </div>
                    <div class="terms-full-row">
                        <div class="terms-item" style="flex:1;">
                            <label class="terms-total">m. Total Down: (Sum h:k)</label>
                            <input type="text" id="totalDown" name="totalDown" class="currency-input" readonly>
                        </div>
                        <div class="terms-item" style="flex:1;">
                            <div class="terms-amount-financed">
                                <span>Amount Financed (l-m):</span>
                                <input type="text" id="amountFinanced" name="amountFinanced" class="currency-input" style="width:150px;" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row" style="justify-content:center; margin-top:30px;">
                <button type="submit" class="submit-btn">Submit Application</button>
            </div>
        </form>

        <div class="footer">
             Form provided by pinnacleautofinance.com — brokered financing solutions portal. | Visit www.pinnacleautofinance.com or call 786.731.8493 to learn more
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Handle submission and data collection
 */
function paf_handle_credit_application_submission() {
    if ( ! isset( $_POST['paf_app_nonce_field'] ) || ! wp_verify_nonce( $_POST['paf_app_nonce_field'], 'paf_submit_app_nonce_action' ) ) {
        wp_die( 'Security check failed.' );
    }
    if ( ! is_user_logged_in() || ! current_user_can('paf_submit_credit_application') ) {
        wp_die( 'Permission denied.' );
    }

    $errors = [];
    $form_data = stripslashes_deep($_POST);

    // Server-side validation (add rules for all required fields)
    if ( empty( $form_data['borrower1_applicant'] ) ) $errors[] = "Primary applicant name is required.";
    if ( empty( $form_data['borrower1_ssn'] ) ) $errors[] = "Primary applicant SSN is required.";
    elseif ( ! preg_match('/^\d{9}$/', str_replace(['-',' '], '', $form_data['borrower1_ssn'])) ) $errors[] = "Primary applicant SSN must be 9 digits.";
    if ( empty( $form_data['vehicleYear'] ) ) $errors[] = "Vehicle year is required.";
    if ( empty( $form_data['sellingPrice'] ) ) $errors[] = "Selling price is required.";
    if ( ! isset($form_data['dealerAttestation']) ) $errors[] = "Dealer attestation is required.";

    if ( ! empty($errors) ) {
        $redirect_url = wp_get_referer();
        if ( $redirect_url ) {
            $redirect_url = add_query_arg( 'paf_form_errors', urlencode( json_encode($errors) ), $redirect_url );
            $redirect_url = add_query_arg( 'paf_form_data', urlencode( json_encode($form_data) ), $redirect_url );
            wp_redirect( $redirect_url ); exit;
        }
        wp_die( 'Form submission error. Errors: ' . implode(', ', $errors) );
    }

    // Collect data into structured arrays
    $primary_borrower_data = paf_collect_borrower_form_data( $form_data, 'borrower1' );
    $co_borrower_data = null;
    if ( ! empty($form_data['borrower2_applicant']) ) {
        $co_borrower_data = paf_collect_borrower_form_data( $form_data, 'borrower2' );
    }

    $vehicle_data = [
        'year'        => sanitize_text_field($form_data['vehicleYear'] ?? ''),
        'makeAndModel'=> sanitize_text_field($form_data['vehicleMakeModel'] ?? ''),
        'trim'        => sanitize_text_field($form_data['vehicleTrim'] ?? ''),
        'mileage'     => intval($form_data['vehicleMileage'] ?? 0),
        'vin'         => sanitize_text_field($form_data['vehicleVIN'] ?? ''),
    ];

    $financial_data = [
        'sellingPrice' => paf_sanitize_currency($form_data['sellingPrice'] ?? '0'),
        'warranty'     => paf_sanitize_currency($form_data['warranty'] ?? '0'),
        'gap'          => paf_sanitize_currency($form_data['gap'] ?? '0'),
        'other'        => paf_sanitize_currency($form_data['other'] ?? '0'),
        'taxRate'      => preg_replace('/[^\d\.]/','', $form_data['taxRate'] ?? '0'),
        'taxes'        => paf_sanitize_currency($form_data['taxes'] ?? '0'),
        'docFees'      => paf_sanitize_currency($form_data['docFees'] ?? '0'),
        'titleFees'    => paf_sanitize_currency($form_data['titleFees'] ?? '0'),
        'totalPrice'   => paf_sanitize_currency($form_data['totalPrice'] ?? '0'),
        'totalCashDown'=> paf_sanitize_currency($form_data['cashDown'] ?? '0'),
        'tradeValue'   => paf_sanitize_currency($form_data['tradeValue'] ?? '0'),
        'tradePayoff'  => paf_sanitize_currency($form_data['tradePayoff'] ?? '0'),
        'netTradeValue'=> paf_sanitize_currency($form_data['netTradeValue'] ?? '0'),
        'totalDown'    => paf_sanitize_currency($form_data['totalDown'] ?? '0'),
        'amountFinanced'=> paf_sanitize_currency($form_data['amountFinanced'] ?? '0'),
    ];

    // Create application post, save meta, schedule jobs, redirect
    $app_id = paf_create_credit_application_post( $primary_borrower_data, $co_borrower_data, $vehicle_data, $financial_data );
    if ( is_wp_error($app_id) ) wp_die( 'Error creating credit application: ' . $app_id->get_error_message() );

    update_post_meta($app_id, '_status', 'pending_submission_to_dealertrack');
    update_post_meta($app_id, '_primary_borrower', wp_json_encode($primary_borrower_data));
    if ( $co_borrower_data ) update_post_meta($app_id, '_co_borrower', wp_json_encode($co_borrower_data));
    update_post_meta($app_id, '_vehicle_data', wp_json_encode($vehicle_data));
    update_post_meta($app_id, '_financial_data', wp_json_encode($financial_data));
    update_post_meta($app_id, '_submission_date', current_time('mysql'));
    update_post_meta($app_id, '_dealer_name', sanitize_text_field($form_data['dealerName'] ?? ''));
    update_post_meta($app_id, '_dealer_telephone', sanitize_text_field($form_data['telephone'] ?? ''));
    update_post_meta($app_id, '_dealer_contact', sanitize_text_field($form_data['contact'] ?? ''));
    update_post_meta($app_id, '_dealer_attestation', isset($form_data['dealerAttestation']));
    update_post_meta($app_id, '_signature_date', sanitize_text_field($form_data['signatureDate'] ?? ''));

    // History entry & schedule submission
    paf_add_history_entry($app_id, 0, 'credit_app_submitted', 'Credit application submitted by dealer.', [
        'user_id' => get_current_user_id()
    ]);
    paf_schedule_automation_job($app_id, null, 'submit_credit_app_to_dealertrack');

    // Redirect to confirmation page
    $success_url = add_query_arg('app_ref', $app_id, home_url('/application-submitted-confirmation/'));
    wp_redirect($success_url);
    exit;
}

/**
 * Collect borrower data helper
 */
function paf_collect_borrower_form_data($form_data, $prefix) {
    $get_val = function($key, $is_numeric = false, $is_currency = false) use ($form_data, $prefix) {
        $full = "{$prefix}_{$key}";
        $val = $form_data[$full] ?? null;
        if ($is_currency && $val) return paf_sanitize_currency($val);
        if ($is_numeric && $val)  return intval($val);
        return sanitize_text_field($val);
    };
    $get_radio = function($key) use ($form_data, $prefix) {
        $full = "{$prefix}_{$key}";
        return sanitize_text_field($form_data[$full] ?? '');
    };

    $data = [
        'personalInformation' => [
            'applicantName'   => $get_val('applicant'),
            'dateOfBirth'     => $get_val('dob'),
            'ssn'             => str_replace(['-',' '], '', $get_val('ssn') ?? ''),
            'driversLicense'  => $get_val('driversLicense'),
            'homePhone'       => $get_val('homePhone'),
            'workPhone'       => $get_val('workPhone'),
            'cellPhone'       => $get_val('cellPhone'),
            'email'           => sanitize_email($form_data["{$prefix}_email"] ?? ''),
        ],
        'addressInformation' => [
            'current'   => [
                'address'      => $get_val('currentAddress'),
                'addressType'  => $get_radio('addressType'),
                'landlordInfo' => $get_val('landlordInfo'),
                'years'        => $get_val('currentAddressYears', true),
                'months'       => $get_val('currentAddressMonths', true),
            ],
            'previous1' => null,
            'previous2' => null,
        ],
        'employmentInformation' => [
            'current'   => [
                'employerName'    => $get_val('currentEmployer'),
                'phone'           => $get_val('employerPhone'),
                'title'           => $get_val('title'),
                'employerAddress' => $get_val('employerAddress'),
                'income'          => $get_val('income', false, true),
                'years'           => $get_val('employmentYears', true),
                'months'          => $get_val('employmentMonths', true),
                'otherIncome'     => $get_val('otherIncome', false, true),
                'incomeSource'    => $get_val('incomeSource'),
            ],
            'previous1' => null,
            'previous2' => null,
        ],
        'financialInformation' => [
            'alimonyPaymentsObliged' => $get_radio('alimonyPayments') === 'yes',
            'alimonyRecipient'       => $get_val('alimonyRecipient'),
            'alimonyAmount'          => $get_val('alimonyAmount', false, true),
            'isCoMaker'              => $get_radio('coMaker') === 'yes',
            'coMakerFor'             => $get_val('coMakerFor'),
            'coMakerToWhom'          => $get_val('coMakerToWhom'),
            'hasJudgments'           => $get_radio('judgments') === 'yes',
            'judgmentToWhom'         => $get_val('judgmentToWhom'),
            'judgmentAmount'         => $get_val('judgmentAmount', false, true),
            'hasBankruptcy'          => $get_radio('bankruptcy') === 'yes',
            'bankruptcyWhere'        => $get_val('bankruptcyWhere'),
            'bankruptcyYear'         => $get_val('bankruptcyYear'),
        ],
    ];

    // Include previous addresses/employers if filled
    if (!empty($form_data["{$prefix}_previousAddress1"])) {
        $data['addressInformation']['previous1'] = [
            'address'      => $get_val('previousAddress1'),
            'landlordInfo' => $get_val('prevAddress1LandlordInfo'),
            'years'        => $get_val('prevAddress1Years', true),
            'months'       => $get_val('prevAddress1Months', true),
        ];
    }
    if (!empty($form_data["{$prefix}_previousAddress2"])) {
        $data['addressInformation']['previous2'] = [
            'address'      => $get_val('previousAddress2'),
            'landlordInfo' => $get_val('prevAddress2LandlordInfo'),
            'years'        => $get_val('prevAddress2Years', true),
            'months'       => $get_val('prevAddress2Months', true),
        ];
    }
    if (!empty($form_data["{$prefix}_previousEmployer1"])) {
        $data['employmentInformation']['previous1'] = [
            'employerName'    => $get_val('previousEmployer1'),
            'phone'           => $get_val('prevEmployer1Phone'),
            'title'           => $get_val('prevEmployer1Title'),
            'employerAddress' => $get_val('prevEmployer1Address'),
            'income'          => $get_val('prevEmployer1Income', false, true),
            'years'           => $get_val('prevEmployment1Years', true),
            'months'          => $get_val('prevEmployment1Months', true),
        ];
    }
    if (!empty($form_data["{$prefix}_previousEmployer2"])) {
        $data['employmentInformation']['previous2'] = [
            'employerName'    => $get_val('previousEmployer2'),
            'phone'           => $get_val('prevEmployer2Phone'),
            'title'           => $get_val('prevEmployer2Title'),
            'employerAddress' => $get_val('prevEmployer2Address'),
            'income'          => $get_val('prevEmployer2Income', false, true),
            'years'           => $get_val('prevEmployment2Years', true),
            'months'          => $get_val('prevEmployment2Months', true),
        ];
    }

    return $data;
}
?>