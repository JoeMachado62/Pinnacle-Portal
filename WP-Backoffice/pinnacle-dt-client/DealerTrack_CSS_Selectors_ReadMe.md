DealerTrack Credit Application Process CSS selectors

In order to map on-screen flow for puppeteer developer I will describe input CCS selector ID from left to right on the screen and then line-by=line staring at the top. I will also number these fields so there is no doubt as to their order.

// (1) Under vehicle Type we select Radio Button "Auto" 
#id_applicant_form-asset_type_1    


// (2) Under Type of Auto our defaut will be 'Used' click radial button
.inline , #id_vehicle_form-condition_type_2 

// (3) Next choice is"Add co-borrower" Default in NO which reads as Selector #id_applicant_form-has_coapplicant_2, If needed must Click Yes to add a coborrower and selector is #id_applicant_form-has_coapplicant_1

// (4) This indicates transaction is either Retail, Lease. Balloon or Other. Default is Retail, we just need to verify it selected.
#id_vehicle_form-product_type_1

// (5) This is the "NEXT" button that advances to next page. 
#btn_app_pref_submit

Unless otherwise indicated all the css selector ID that follow can be tagged input if that helps. (i.e. 'input.#sample_selector_id)

// (6) This is Applicants First Name 
Selector ID: #id_applicant_form-first_name

// (7) Applicant Middle Name if any.
Selector ID: #id_applicant_form-middle_name

// (8) This is Applicant Last Name
Selector ID: #id_applicant_form-last_name

// (9) This is Applicant Suffix drop down choices include (Jr., Sr., I, II, III and IV)
Selector ID: #id_applicant_form-suffix_code 

// (10)  Applicant's SSN
Selector ID: #id_applicant_form-tax_id

// (11)  Applicants Date of Birth.
Selector ID: #id_applicant_form-birth_date

// (12) Applicant Home Address
Selector ID: #id_applicant_form-line_1_address

// (13) Applicant Home Address line 2
Selector ID: #id_applicant_form-line_2_address

// (14) Applicant Home Address City
Selector ID: #id_applicant_form-city

// (15) Applicant Home Address State
Selector ID: #id_applicant_form-us_state_code

// (16) Applicant Home Address Zip code
Selector ID: #id_applicant_form-zip_code

// (17) Applicant Primary Phone
Selector ID: #id_applicant_form-primary_phone_number

// (18) Applicant_form-alternate_phone_number
Selector ID: #id_applicant_form-alternate_phone_number

// (19) Applicant_Email Address.  (If WordPress data has not email the click Selector: Selector ID: #id_co_applicant_form-email_not_provided)
Selector ID: #id_applicant_form-email_address

// (20) Applicant License State Issued
Selector ID: #id_applicant_form-drivers_license_us_state_code

// (21) Applicant License Number
Selector ID: #id_applicant_form-drivers_license

// (22) Next field is Housing status with a drop-down with multiple choices line (Rent, Mortgage, Family, Military or Other, plus Own Outright.)
Selector ID: #id_applicant_form-housing_status_code

// (23) Applicant Years at  Address (note if 2 or more years won’t have previous address fields)
Selector ID: #id_applicant_form-current_address_years

// (24) Applicant Current Address Months
Selector ID: #id_applicant_form-current_address_months

// (25) Applicant Current Address Rent/Mortgage  (NOTE THAT THIS INPUT DOES NOT EXIST FOR OWN OUTRIGHT)
Selector ID: #id_applicant_form-mortgage_payment_or_rent

// (26) Applicant Previous Address State
Selector ID: #id_applicant_form-previous_line_1_address

// (27) Applicant Previous Address line 2
Selector ID: #id_applicant_form-previous_line_2_address

// (28) Applicant Previous Address Zip
Selector ID: #id_applicant_form-previous_zip_code

// (29) Applicant Previous Address City & State 
Selector ID: #id_applicant_form-previous_city_state_dropdown
This drop contains the names of City and State combinations that Dealer Track may have registered for the Zip-code enter in Step 28. To avoid complex logic we can hard code to always chose “other” in dropdown, When “Other” is chosen the dropdown field dynamically changes into two separate input field. We will call those steps (29a) for City and (29b) for State.
(29a) City = Selector Selector ID: #id_applicant_form-previous_city  (29b) State = Selector Selector ID: #id_applicant_form-previous_us_state_code
Then Puppetter can simply input the City and State provided from the WordPress application data received.

// (30) Applicant Years at Previous Address
Selector ID: #id_applicant_form-previous_address_years

// (31) Applicant Months at Previous Address
Selector ID: #id_applicant_form-previous_address_months
THE NEXT SECTION IS EMPLOYMENT INFORMATION AND REQUIRES SOME EXPLANATION FOR CONTEXT TO UNDERSTAND HOW SECTION CHANGES DYNAMICALLY BASED ON TYPE OF EMPLOYMENT AND LENGTH OF TIME AT EMPLOYEER. For example, If the Applicant has been at current employment for 2 years or more there will be no input fields for Previous Employment input fields appear if current employment is less than 2 years. 
// (32) Applicant Employment Status dropdown (Employed, Unemployed, Retired, Active Military, Other, Self-Employed, Student & Retired Military)
Selector ID: #id_applicant_form-employment_status_code 
(The choice made here will dynamically affect the input fields that follow so I will describe each path by the step number”32” and letter “x” for the dropdown choice. For example 32a=Employed, 32b=Unemployed, 32c=Retired,32d=Active Military, 32e= Other, 32f=Self-Employed, 32g=Student and 32h=Retired Military.
	// (32a) Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_months
	Input 4;  #Applicant Work phone number
	Selector ID: Selector ID: #id_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_applicant_form-occupation_name
	Input 6;  Applicant Salary
	Selector ID: Selector ID: #id_applicant_form-salary
	Input 7;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_applicant_form-salary_type_code


	// (32b) Unemployed
	Input 1; Other Income
	Selector ID:  # Selector ID: #id_applicant_form-other_monthly_income
	Input 2;  Source of Other Income
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years
	Selector ID: #id_applicant_form-other_income_source
	
// (32c) Retired 
	Input 1; Expected Salary
	Selector ID:  Selector ID: #id_applicant_form-salary
	Input 2;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID:  Selector ID: #id_applicant_form-salary_type_code
	Input 3;  Applicant Other Income
	Selector ID:  Selector ID: #id_applicant_form-other_monthly_income
	Input 4;  Applicant Other Income Source
	Selector ID:  Selector ID: #id_applicant_form-other_income_source
	
	// (32d) Active Military
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_months
	Input 4;  #Applicant Work phone number
	Selector ID: Selector ID: #id_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_applicant_form-occupation_name
	Input 6;  Applicant Salary
	Selector ID: Selector ID: #id_applicant_form-salary
	Input 7;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_applicant_form-salary_type_code

	// (32e) Other
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_months
	Input 4;  #Applicant Work phone number
	Selector ID: Selector ID: #id_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_applicant_form-occupation_name
	Input 6;  Applicant Salary
	Selector ID: Selector ID: #id_applicant_form-salary
	Input 7;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_applicant_form-salary_type_code

	// (32f) Self Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_applicant_form-current_employed_months
	Input 4;  #Applicant Work phone number
	Selector ID: Selector ID: #id_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_applicant_form-occupation_name
	Input 6;  Applicant Salary
	Selector ID: Selector ID: #id_applicant_form-salary
	Input 7;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_applicant_form-salary_type_code

	// (32g) Student
	Input 1; Applicant School
	Selector ID:  Selector ID: #id_applicant_form-school
	Input 2;  Student-Salary
	Selector ID:  Selector ID: #id_applicant_form-salary
	Input 3;  Applicant Pay Frequency
	Selector ID: Selector ID: #id_applicant_form-salary_type_code
	Input 4;  #Applicant Other Income
	Selector ID:  Selector ID: #id_applicant_form-other_monthly_income
	Input 2;  Source of Other Income
	Selector ID:  Selector ID: #id_applicant_form-current_employed_years

	
	// (32h) Retired Military
	Input 1; Expected Salary
	Selector ID:  Selector ID: #id_applicant_form-salary
	Input 2;  Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID:  Selector ID: #id_applicant_form-salary_type_code
	Input 3;  Applicant Other Income
	Selector ID:  Selector ID: #id_applicant_form-other_monthly_income
	Input 4;  Applicant Other Income Source
	Selector ID:  Selector ID: #id_applicant_form-other_income_source

NOTE THAT PREVIOUS EMPLOYMENT INPUT FIELDS WILL ONLY APPEARS IN TIME AT CURRENT EMPLOYER IS LESS THAN 2 YEARS. PUPPETEER CODE LOGIC NEEDS TO SKIP THIS SECTION AND NOT LOOK FOR THESE FIELDS IN THOSE INSTANCES.
Applicant Previous Employment Status dropdown (Employed, Unemployed, Retired, Active Military, Other, Self-Employed, Student & Retired Military)
Selector ID: #id_Applicant Previous_form-employment_status_code 
(The choice made here will dynamically affect the input fields that follow so I will describe each path by the step number”33” and letter “x” for the dropdown choice. For example 33a=Employed, 33b=Unemployed, 33c=Retired,33d=Active Military, 33e= Other, 33f=Self-Employed, 33g=Student and 33h=Retired Military.
	// (33a) Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_months
	Input 4;  Applicant Previous Work phone number
	Selector ID: Selector ID: #id_Applicant Previous_form-work_phone_number
	Input 5;   Occupation
Selector ID# Selector ID: #id_applicant_form-previous_occupation_name

	// (33b) Unemployed
	No other fields beyond Type selected
	
// (33c) Retired
	No other fields beyond Type selected
	
	// (33d) Active Military
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_months
	Input 4;  Applicant Previous Work phone number
	Selector ID: Selector ID: #id_Applicant Previous_form-work_phone_number
	Input 5;   Occupation
Selector ID# Selector ID: #id_applicant_form-previous_occupation_name


	// (33e) Other
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_months
	Input 4;  Applicant Previous Work phone number
	Selector ID: Selector ID: #id_Applicant Previous_form-work_phone_number
	Input 5;   Occupation
Selector ID# Selector ID: #id_applicant_form-previous_occupation_name

	// (33f) Self Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_Applicant Previous_form-current_employed_months
	Input 4;  Applicant Previous Work phone number
	Selector ID: Selector ID: #id_Applicant Previous_form-work_phone_number
	Input 5;   Occupation
Selector ID# Selector ID: #id_applicant_form-previous_occupation_name



	// (33g) Student
	Input 1; Applicant Previous School
	Selector ID:  Selector ID: #id_applicant_form-previous_school
	
	// (33h) Retired Military
	No additional inputs other than type selection.

THIS OTHER INCOME SECTION WILL APPEAR AFTER ALL PREVIOUS EMPLOYMENT SECTION UNLESS APPLICANT HAS BEEN AT CURRENT EMPLOYMENT FOR 2 YEARS OR MORE IN WHICH CASE IT WILL APPEAR AFTER COMPLETION OF WHICH EVER EMPLOYMENT TYPE PROCES WAS FOLLOWED. (32a thru 32h.) INF OTHER WORDS PUPPETEER CODE WOULD SKIP PRIOR SCTION ON PREVIOUS EMPLOYMENTS WHEN CURRENT EMPLOYMENT IS 2 YERS OR GREATER. 
// (34) Applicant Other Income
Selector ID: #id_applicant_form-other_monthly_income

// (35) Applicant Source of Other Income
Selector ID: #id_applicant_form-other_income_source

CO-APPLICANT INFORMATION THAT FOLLOWS WILL ONLY APPEAR IF JOINT APPLICATION IS SELECTED. ALSO NOTE THAT ADDRESS AND PREVIOUS ADDRESS FIELDS WILL BE AUTO COMPLETED WITH SELECTOR CHECK BOX “Same as Applicant Address” OF THIS SECTION.
// (36) Checkbox “Same as Applicant Address” (default is unchecked but can click it if co-applicant resides with applicant. Selecting this will remove the collection of separate Housing expense information from co-applicant. For the purposes of providing context all all relevant Selector ID’s I will run thru the longer version here but for puporses of coding your Puppeteer Logic I will leave that to you to find the best way.
Selector ID: Selector ID: #id_co_applicant_form-same_address

// (37)  Relationship to Applicant (This dropdown contains 3 choices, Spouse, Relative or Other)
Selector ID:  Selector ID: #id_co_applicant_form-party_relationship_code

// (38) This is Co-Applicants First Name 
Selector ID:  #id_co_applicant_form-first_name

// (39) Co-Applicant Middle Name if any.
Selector ID:  #id_co_applicant_form-middle_name

// (40) This is Co-Applicant Last Name
Selector ID:  #id_co_applicant_form-last_name

// (41) This is Co-Applicant Suffix drop down choices include (Jr., Sr., I, II, III and IV)
Selector ID:  #id_co_applicant_form-suffix_code
// (42)  Co-Applicant's SSN
Selector ID:  #id_co_applicant_form-tax_id

// (43)  Co-Applicants Date of Birth.
Selector ID:  #id_co_applicant_form-birth_date

// (44) Co-Applicant Home Address
Selector ID:  #id_co_applicant_form-line_1_address

// (45) Co-Applicant Home Address line 2
Selector ID:  #id_co_applicant_form-line_2_address

// (46) Co-Applicant Home Address City
Selector ID:  #id_co_applicant_form-city

// (47) Co-Applicant Home Address State
Selector ID:  #id_co_applicant_form-us_state_code

// (48) Co-Applicant Home Address Zip code
Selector ID:  #id_co_applicant_form-zip_code

// (49) Co-Applicant Primary Phone
Selector ID:  #id_co_applicant_form-primary_phone_number


// (50) Co-Applicant_form-alternate_phone_number
Selector ID:  #id_co_applicant_form-alternate_phone_number

// (51) Co-Applicant_Email Address.  (If WordPress data has not email the click Selector: Selector ID: #id_co_applicant_form-email_not_provided)
Selector ID:  #id_co_applicant_form-email_address

// (52) Co-Applicant License State Issued
Selector ID:  #id_co_applicant_form-drivers_license_us_state_code

// (53) Co-Applicant License Number
Selector ID:  #id_co_applicant_form-drivers_license

// (54) Next field is Housing status with a drop-down with multiple choices line (Rent, Mortgage, Family, Military or Other. Plus Own Outright)
Selector ID:  #id_co_applicant_form-housing_status_code

// (55) Co-Applicant Years at  Address (note if 2 or more years won’t have previous address fields)
Selector ID:  #id_co_applicant_form-current_address_years

// (56) Co-Applicant Current Address Months
Selector ID:  #id_co_applicant_form-current_address_years

// (57) Co-Applicant Current Address Rent/Mortgage
Selector ID:  #id_co_applicant_form-mortgage_payment_or_rent

// (58) Co-Applicant Previous Address State
Selector ID:  #id_co_applicant_form-previous_line_1_address

// (59) Co-Applicant Previous Address line 2
Selector ID:  #id_co_applicant_form-previous_line_2_address

// (60) Co-Applicant Previous Address Zip
Selector ID:  #id_co_applicant_form-previous_zip_code

// (61) Co-Applicant Previous Address City & State 
Selector ID:  #id_co_applicant_form-previous_city_state_dropdown
This drop contains the names of City and State combinations that Dealer Track may have registered for the Zip-code enter in Step 28. To avoid complex logic we can hard code to always chose “other” in dropdown, When “Other” is chosen the dropdown field dynamically changes into two separate input field. We will call those steps (61a) for City and (61b) for State.
(61a) City = Selector ID:  #id_co_applicant_form-previous_city  (61b) State = Selector Selector ID:  #id_co_applicant_form-previous_us_state_code
Then Puppeteer can simply input the City and State provided from the WordPress application data received.

// (62) Co-Applicant Years at Previous Address
Selector ID:  #id_co_applicant_form-previous_address_years

// (63) Co-Applicant Months at Previous Address
Selector ID:  #id_co_applicant_form-previous_address_months

THE NEXT SECTION IS CO-APPLICANT EMPLOYMENT INFORMATION AND REQUIRES SOME EXPLANATION FOR CONTEXT TO UNDERSTAND HOW SECTION CHANGES DYNAMICALLY BASED ON TYPE OF EMPLOYMENT AND LENGTH OF TIME AT EMPLOYEER. For example, If the Co-Applicant has been at current employment for 2 years or more there will be no input fields for Previous Employment input fields appear if current employment is less than 2 years. 
// (64) Co-Applicant Employment Status dropdown (Employed, Unemployed, Retired, Active Military, Other, Self-Employed, Student & Retired Military)
Selector ID: Selector ID: #id_co_applicant_form-employment_status_code 
(The choice made here will dynamically affect the input fields that follow so I will describe each path by the step number”63” and letter “x” for the dropdown choice. For example 63a=Employed, 63b=Unemployed, 63c=Retired,63d=Active Military, 63e= Other, 63f=Self-Employed, 63g=Student and 63h=Retired Military.
	// (63a) Employed
	Input 1; Employer Name
	Selector ID:   Selector ID: #id_co_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_months
	Input 4;  #Co-Applicant Work phone number
	Selector ID: Selector ID: #id_co_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_co_applicant_form-occupation_name
	Input 6;  Co-Applicant Salary
	Selector ID: Selector ID: #id_co_applicant_form-salary
	Input 7;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_co_applicant_form-salary_type_code

	// (63b) Unemployed
	Input 1; Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-other_monthly_income
	Input 2;  Source of Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-other_income_source
	// (63c) Retired
	Input 1; Expected Salary
	Selector ID:  Selector ID: #id_co_applicant_form-salary
	Input 2;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID:  Selector ID: #id_co_applicant_form-salary_type_code
	Input 3;  Co-Applicant Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-other_monthly_income
	Input 4;  Co-Applicant Other Income Source
	Selector ID:  Selector ID: #id_co_applicant_form-other_income_source
	
	// (63d) Active Military
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_months
	Input 4;  #Co-Applicant Work phone number
	Selector ID: Selector ID: #id_co_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_co_applicant_form-occupation_name
	Input 6;  Co-Applicant Salary
	Selector ID: Selector ID: #id_co_applicant_form-salary
	Input 7;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_co_applicant_form-salary_type_code

	// (63e) Other
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_months
	Input 4;  #Co-Applicant Work phone number
	Selector ID: Selector ID: #id_co_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_co_applicant_form-occupation_name
	Input 6;  Co-Applicant Salary
	Selector ID: Selector ID: #id_co_applicant_form-salary
	Input 7;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_co_applicant_form-salary_type_code

	// (63f) Self Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-organization_name
	Input 2;  Year at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_years
	Input 3;  Months at Employer
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_months
	Input 4;  #Co-Applicant Work phone number
	Selector ID: Selector ID: #id_co_applicant_form-work_phone_number
	Input 5;   Occupation
	Selector ID:  Selector ID: #id_co_applicant_form-occupation_name
	Input 6;  Co-Applicant Salary
	Selector ID: Selector ID: #id_co_applicant_form-salary
	Input 7;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID: Selector ID: #id_co_applicant_form-salary_type_code

	// (63g) Student
	Input 1; Co-Applicant School
	Selector ID:  Selector ID: #id_co_applicant_form-school
	Input 2;  Student-Salary
	Selector ID:  Selector ID: #id_co_applicant_form-salary
	Input 3;  Co-Applicant Pay Frequency
	Selector ID: Selector ID: #id_co_applicant_form-salary_type_code
	Input 4;  #Co-Applicant Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-other_monthly_income
	Input 2;  Source of Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-current_employed_years
	
	// (63h) Retired Military
	Input 1; Expected Salary
	Selector ID:  Selector ID: #id_co_applicant_form-salary
	Input 2;  Co-Applicant Pay Frequency dropdown choose 1 ( Weekly, Bi-Weekly, Monthly or Annually)
	Selector ID:  Selector ID: #id_co_applicant_form-salary_type_code
	Input 3;  Co-Applicant Other Income
	Selector ID:  Selector ID: #id_co_applicant_form-other_monthly_income
	Input 4;  Co-Applicant Other Income Source
	Selector ID:  Selector ID: #id_co_applicant_form-other_income_source

NOTE THAT PREVIOUS EMPLOYMENT INPUT FIELDS WILL ONLY APPEARS IN TIME AT CURRENT EMPLOYER IS LESS THAN 2 YEARS. PUPPETEER CODE LOGIC NEEDS TO SKIP THIS SECTION AND NOT LOOK FOR THESE FIELDS IN THOSE INSTANCES.
// (64) Previous Employment Status
Selector ID: Selector ID: #id_co_applicant_form-previous_employment_status_code
Co-Applicant Previous Employment Status dropdown (Employed, Unemployed, Retired, Active Military, Other, Self-Employed, Student & Retired Military)
(The choice made here will dynamically affect the input fields that follow so I will describe each path by the step number”64” and letter “x” for the dropdown choice. For example 64a=Employed, 64b=Unemployed, 64c=Retired,64d=Active Military, 64e= Other, 64f=Self-Employed, 64g=Student and 64h=Retired Military.
	// (64a) Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant Previous_form-current_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant Previous_form-current_employed_months
	Input 4;  Co-Applicant Previous Work phone number
	Selector ID: Selector ID: #id_co_applicant Previous_form-work_phone_number
	Input 5;   Occupation
Selector ID: Selector ID: #id_co_applicant_form-previous_occupation_name

	// (64b) Unemployed
	No other fields beyond Type selected
	// (64c) Retired
	No other fields beyond Type selected
	
	// (64d) Active Military
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_years
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_months
	Input 4;  Co-Applicant Previous Work phone number
	Selector ID:  Selector ID: #id_co_applicant_form-previous_work_phone_number
	Input 5;   Occupation
Selector ID:  Selector ID: #id_co_applicant_form-previous_occupation_name


	// (64e) Other
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_months
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_months
	Input 4;  Co-Applicant Previous Work phone number
	Selector ID:  Selector ID: #id_co_applicant_form-previous_work_phone_number
	Input 5;   Occupation
Selector ID:  Selector ID: #id_co_applicant_form-previous_occupation_name

	// (64f) Self Employed
	Input 1; Employer Name
	Selector ID:  Selector ID: #id_co_applicant_form-previous_organization_name
	Input 2;  Years at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_months
	Input 3;  Months at Previous Employer
	Selector ID:  Selector ID: #id_co_applicant_form-previous_employed_months
	Input 4;  Co-Applicant Previous Work phone number
	Selector ID:  Selector ID: #id_co_applicant_form-previous_work_phone_number
	Input 5;   Occupation
Selector ID:  Selector ID: #id_co_applicant_form-previous_occupation_name


	// (64g) Student
	Input 1; Co-Applicant Previous School
	Selector ID:  Selector ID: #id_co_applicant_form-previous_school
	
	// (64h) Retired Military
	No additional inputs other than type selection.


THIS SECTION IS FOR TRANSACTION DEATAILS AND VEHICLE INFORMATION. NOTE MOST APPLICATION WON’T HAVE A STOCK NUMBER BUT WILL ALL CONTAIN A VIN NUMBER. VIN NUMBER HAS AUTOMATIC DECODER WHICH FILLS OUT DEALERTRACK INPUTS AND GREYS THEM OUT SO PUPPETEER WON’T BE ABLE TO INPUT DATA. IN EVERY INSTANCE THOSE DETAILS WILL INCLUDE THE YEAR, MAKE AND MODEL OF THE VEHICLE, AND IN SOME INSTANCES THAT WILL ALSO INCLUDE THE TRIM LEVEL. I WILL STILL PROVIDE THE INPUT FILEDS IN THE ORDER THEY APPEAR, BUT FOR PURPOSES OF THE PUPPETEER CODE JUST REMEMBER TO INCLUDE just a bit of guard-logic around your normal fill routine to watch for a disabled (or read-only) field and skip it if so.

// (65) ‘Payment Call’  Radio button Yes No  (default is no)
For YES Selector ID: #id_vehicle_form-payment_call_indicator_1 for NO Selector ID: #id_vehicle_form-payment_call_indicator_2


// (66) Stock Number  (Not available on most submissions)
Selector ID: #id_vehicle_form-stock_number

//  (67) Vin Number   (Note VIN decoder will auto populate Year, Make and Model)
Selector ID: #id_vehicle_form-vin_number

// (68) Vehicle Year 
Selector ID: #id_vehicle_form-year_id

// (69) Vehicle Make 
Selector ID: #id_vehicle_form-make_id

// (70) Vehicle Model
Selector ID: #id_vehicle_form-model_id

// (71) Vehicle Trim
Selector ID: #id_vehicle_form-trim_id

// (72)  Odometer reading (Miles)
Selector ID: #id_vehicle_form-odometer_number

Final Section is Terms
// (73)  Terms (total months of financing)
Selector ID: #id_vehicle_form-term_count

// (74)  Cash Selling Price
Selector ID: #id_vehicle_form-cash_sell_price_amount

// (75)  Sales Tax
Selector ID: #id_vehicle_form-sales_tax_amount

// (76) Title and Tag Transfer fees
Selector ID: #id_vehicle_form-title_and_license_amount

// (77)  Cash Down Payment
Selector ID: #id_vehicle_form-cash_down_amount

// (78) Front End Fees
Selector ID: #id_vehicle_form-front_end_fee_amount

Footnotes and Field Syntax:
Phone number require following format: (212) 731-5555 i.e. (###) ###-####
SSN Tax ID Require: 9 digits #########
DATES Require:  ##/##/#### i.e. 05/21/1992
Emails require: Anyname@domain.anyvalidextension (.com,.net, .any valid domain extension)

Special Note: If Retired or Retired Military is chosen as the Primary employment “Year on Job” and “Prior Employment wont be collected” 
