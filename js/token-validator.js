(function() {
    document.addEventListener("DOMContentLoaded", function() {
      const token = localStorage.getItem('jwt');
      if (!token) {
        return;
      }
      
      fetch('./backend/auth-api/token-validation-api.php', {
        headers: {
          'Authorization': 'Bearer ' + token
        }
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (data.status === 'expired' || data.status === 'invalid') {
          localStorage.removeItem('jwt');
          showTokenExpiredModal();
        }
      })
      .catch(function(error) {
        console.error('Error during token validation:', error);
      });
    });
  
    function showTokenExpiredModal() {
      if (document.getElementById('tokenExpiredModal')) {
        return;
      }
      
      const modalHtml = `
        <div class="modal fade" id="tokenExpiredModal" tabindex="-1" aria-labelledby="tokenExpiredModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="tokenExpiredModalLabel">Session Expired</h5>
              </div>
              <div class="modal-body">
                Your session has expired or is invalid. Please sign in again.
              </div>
              <div class="modal-footer">
                <button type="button" id="redirectToSignIn" class="btn btn-primary">Sign In</button>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.insertAdjacentHTML('beforeend', modalHtml);
  
      const modalEl = document.getElementById('tokenExpiredModal');
      const modal = new bootstrap.Modal(modalEl, {});
      modal.show();
  
      document.getElementById('redirectToSignIn').addEventListener('click', function() {
        window.location.href = 'sign-in.html';
      });
    }
  })();
  