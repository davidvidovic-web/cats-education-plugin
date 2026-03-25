document.addEventListener("DOMContentLoaded", function () {
  const popup = document.getElementById("register-popup");
  
  // Only target the form INSIDE the popup, not any other forms on the page
  const popupForm = popup ? popup.querySelector('#register-form') : null;
  
  if (!popupForm) {
      return;
  }
  
  // Open Popup Logic
  document.body.addEventListener('click', function(e) {
    if (e.target.closest('.open-register-popup')) {
      e.preventDefault();
      const button = e.target.closest('.open-register-popup');
      
      // Get data from button
      const date = button.dataset.date || '';
      const heading = button.dataset.heading || '';
      const desc = button.dataset.desc || '';
      const discountDates = button.dataset.discountDates || '';
      const discountIndividual = button.dataset.discountIndividual || '';
      const discountGroup = button.dataset.discountGroup || '';
      const owner = button.dataset.owner || '';
      
      // Helper function to set or create hidden field
      const setHiddenField = (fieldName, fieldValue) => {
        let input = popupForm.querySelector(`[name="${fieldName}"]`);
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = fieldName;
          popupForm.appendChild(input);
        }
        input.value = fieldValue;
      };
      
      // Populate ALL fields (create if missing due to cache/WordPress issues)
      setHiddenField('education_date', date);
      setHiddenField('popup_heading', heading);
      setHiddenField('popup_desc', desc);
      setHiddenField('discount_dates', discountDates);
      setHiddenField('discount_individual', discountIndividual);
      setHiddenField('discount_group', discountGroup);
      setHiddenField('owner', owner);

      // Show popup
      if(popup) {
          popup.style.display = 'flex';
          document.body.classList.add('popup-open');
      }
    }
  });

  // Close Popup Logic
  if (popup) {
      popup.addEventListener('click', function(e) {
          if (e.target === popup || e.target.classList.contains('close-popup')) {
              popup.style.display = 'none';
              document.body.classList.remove('popup-open');
          }
      });
  }
  
  // Handle Form Submission - ONLY for the popup form
  if (popupForm) {
      popupForm.addEventListener("submit", function (e) {
        e.preventDefault();
        
        // Use the popup form as source of truth
        const formData = new FormData(popupForm);

        // Basic Validation
        const required = ['name', 'email', 'phone'];
        let valid = true;
        let missingFields = [];

        required.forEach(field => {
            // Check value in FormData or Fallback to DOM
            const value = formData.get(field); 
            const input = popupForm.querySelector(`[name="${field}"]`);
            
            if(!value || !value.trim()) {
                valid = false;
                if(input) {
                    input.style.borderColor = 'red';
                    // Try to find label text
                    const label = popupForm.querySelector(`label[for="${input.id}"]`);
                    missingFields.push(label ? label.textContent.replace('*', '').trim() : field);
                } else {
                    missingFields.push(field);
                }
            } else if(input) {
                input.style.borderColor = '';
            }
        });

        // Validate radio button selection
        let tipKursaValue = formData.get('tip_kursa');
        
        // Fallback: Check DOM if FormData missed it (radio might be outside form in some cases)
        if (!tipKursaValue) {
            let checkedRadio = popupForm.querySelector('input[name="tip_kursa"]:checked');
            
            // If not in form, search in popup container
            if (!checkedRadio && popup) {
                checkedRadio = popup.querySelector('input[name="tip_kursa"]:checked');
            }
            
            if (checkedRadio) {
                tipKursaValue = checkedRadio.value;
                formData.append('tip_kursa', tipKursaValue);
            }
        }
        
        if(!tipKursaValue) {
            valid = false;
            let radioGroup = popupForm.querySelector('.radio-options');
            if (!radioGroup && popup) {
                radioGroup = popup.querySelector('.radio-options');
            }
            if(radioGroup) {
                radioGroup.style.border = '1px solid red';
                radioGroup.style.borderRadius = '4px';
                radioGroup.style.padding = '5px';
            }
            missingFields.push("Tip edukacije");
        } else {
            let radioGroup = popupForm.querySelector('.radio-options');
            if (!radioGroup && popup) {
                radioGroup = popup.querySelector('.radio-options');
            }
            if(radioGroup) {
                radioGroup.style.border = '';
                radioGroup.style.padding = '';
            }
        }

        if(!valid) {
            const errorMsg = "Molimo popunite: " + missingFields.join(', ');
            showMessage(errorMsg, "error", popupForm); 
            return;
        }

        // Prepare data for sending
        formData.append("action", "register_popup_form");
        if(typeof registerPopupAjax !== 'undefined') {
            formData.append("nonce", registerPopupAjax.nonce);
        }
        
        // Show loading state
        const submitButton = popupForm.querySelector('button[type="submit"]');
        let originalText = "POTVRDI PRIJAVU"; 
        
        if (submitButton) {
            originalText = submitButton.textContent;
            submitButton.textContent = "Šalje se...";
            submitButton.disabled = true;
        }

        if(typeof registerPopupAjax !== 'undefined') {
            fetch(registerPopupAjax.ajax_url, {
            method: "POST",
            body: formData,
            })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                // Show success message in form
                showMessage(data.data, "success", popupForm);
                popupForm.reset();
                } else {
                // Show error message in form
                showMessage(data.data || "Došlo je do greške.", "error", popupForm);
                }
            })
            .catch((err) => {
                showMessage("Došlo je do greške.", "error", popupForm);
            })
            .finally(() => {
                // Reset button state
                if (submitButton) {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            });
        } else {
            showMessage("Configuration Error: Ajax URL missing", "error", popupForm);
            if (submitButton) {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        }
      });
  }

  function showMessage(message, type, targetForm) {
    // Determine which form to show message in
    const formToUse = targetForm || popupForm || document.getElementById('register-form');
    if (!formToUse) return;

    // Remove any existing message
    const existingMessage = formToUse.querySelector('.popup-message');
    if (existingMessage) {
      existingMessage.remove();
    }

    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `popup-message ${type}`;
    messageDiv.textContent = message;
    
    // Insert message at the top of the form
    formToUse.insertBefore(messageDiv, formToUse.firstChild);

    // Auto-close popup after success
    if (type === 'success') {
      setTimeout(() => {
        if(popup) {
            popup.style.display = "none";
            document.body.classList.remove("popup-open");
        }
        messageDiv.remove();
      }, 3000);
    }
  }
});
