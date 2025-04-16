jQuery(document).ready(function($) {
    // Company/Campaign Cascade
    $('#company_id').on('change', function() {
        const companyId = $(this).val();
        const campaignSelect = $('#campaign_id');
        
        if (!companyId) {
            campaignSelect.prop('disabled', true).html('<option value="">Select Campaign</option>');
            return;
        }

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_company_campaigns',
                company_id: companyId,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Select Campaign</option>';
                    response.data.forEach(function(campaign) {
                        options += `<option value="${campaign.id}">${campaign.name}</option>`;
                    });
                    campaignSelect.html(options).prop('disabled', false);
                }
            },
            error: function() {
                alert('Error loading campaigns');
                campaignSelect.prop('disabled', true);
            }
        });
    });

    // Campaign selection handler
    $('#campaign_id').on('change', function() {
        const campaignId = $(this).val();
        const $podsContainer = $('#pods-container');
        
        if (!campaignId) {
            $podsContainer.html('<p>Select a campaign to view available pods.</p>');
            return;
        }

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_campaign_pods',
                campaign_id: campaignId,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '<div class="pods-grid">';
                    response.data.forEach(pod => {
                        html += `
                            <div class="pod-item">
                                <label>
                                    <input type="checkbox" name="pods[]" value="${pod.id}" class="pod-checkbox">
                                    ${pod.name}
                                </label>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $podsContainer.html(html);

                    // Add change handler for pod checkboxes
                    $('.pod-checkbox').on('change', function() {
                        if ($('.pod-checkbox:checked').length > 0) {
                            updateTeamsPreview();
                        } else {
                            $('#teams-preview').hide();
                        }
                    });
                }
            }
        });
    });

    function updateTeamsPreview() {
        const teamCount = $('#team_count').val();
        const selectedPods = $('.pod-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedPods.length === 0) {
            $('#teams-preview').hide();
            return;
        }

        // Setup team names inputs
        const $namesContainer = $('.team-names-container');
        $namesContainer.empty();

        for (let i = 1; i <= teamCount; i++) {
            $namesContainer.append(`
                <div class="team-name-input">
                    <label for="team_name_${i}">Team ${i} Name:</label>
                    <input type="text" 
                           name="team_names[]" 
                           id="team_name_${i}" 
                           value="Team ${i}" 
                           class="regular-text">
                </div>
            `);
        }

        // Get users from selected pods
        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pod_users',
                pod_ids: selectedPods,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    createTeamsPreview(response.data);
                }
            }
        });
    }

    function createTeamsPreview(users) {
        const teamCount = parseInt($('#team_count').val());
        const container = $('.teams-container');
        container.empty();

        if (!users || users.length === 0) {
            container.html('<p>No users found in selected pods.</p>');
            return;
        }

        // Create team containers
        for (let i = 1; i <= teamCount; i++) {
            const teamName = $(`#team_name_${i}`).val() || `Team ${i}`;
            container.append(`
                <div class="team-box">
                    <h4>${teamName}</h4>
                    <div class="team-members-list" data-team-id="${i}"></div>
                </div>
            `);
        }

        // Distribute users evenly
        const shuffledUsers = shuffleArray([...users]);
        const teamsLists = document.querySelectorAll('.team-members-list');
        
        shuffledUsers.forEach((user, index) => {
            const teamIndex = index % teamCount;
            $(teamsLists[teamIndex]).append(`
                <div class="team-member" data-user-id="${user.ID}">
                    ${user.display_name}
                </div>
            `);
        });

        $('#teams-preview').show();
        initializeTeamsDragDrop();
    }

    // Auto-set End Date
    $('#start_date').on('change', function() {
        const startDate = new Date($(this).val());
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6); // Change from 7 to 6 to include start date
        $('#end_date').val(endDate.toISOString().split('T')[0]);
    });

    // KPI Rules Management
    let rules = [];

    $('#add-rule').on('click', function() {
        const name = $('#rule-name').val();
        const emoji = $('#rule-emoji').val();
        const points = $('#rule-points').val();

        if (!name || !emoji || !points) {
            alert('Please fill in all rule fields');
            return;
        }

        rules.push({ name, emoji, points });
        updateRulesDisplay();
        clearRuleForm();
        updateRulesData();
    });

    function clearRuleForm() {
        $('#rule-name').val('');
        $('#rule-emoji').val('');
        $('#rule-points').val('');
    }

    function updateRulesDisplay() {
        const container = $('#rules-container');
        container.empty();

        rules.forEach((rule, index) => {
            container.append(`
                <div class="rule-item">
                    <div class="rule-emoji">${rule.emoji}</div>
                    <div class="rule-details">
                        <div class="rule-name">${rule.name}</div>
                        <div class="rule-points">${rule.points} points</div>
                    </div>
                    <div class="rule-actions">
                        <button type="button" class="button edit-rule" data-index="${index}">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button delete-rule" data-index="${index}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
        });
    }

    function updateRulesData() {
        $('#rules-data').val(JSON.stringify(rules));
    }

    $(document).on('click', '.edit-rule', function() {
        const index = $(this).data('index');
        const rule = rules[index];
        
        $('#rule-name').val(rule.name);
        $('#rule-emoji').val(rule.emoji);
        $('#rule-points').val(rule.points);
        
        rules.splice(index, 1);
        updateRulesDisplay();
        updateRulesData();
    });

    $(document).on('click', '.delete-rule', function() {
        const index = $(this).data('index');
        rules.splice(index, 1);
        updateRulesDisplay();
        updateRulesData();
    });

    // Team Management
    $('#team_count').on('change', updateTeamsPreview);

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }

    function initializeTeamsDragDrop() {
        const containers = document.querySelectorAll('.team-members-list');
        containers.forEach(container => {
            new Sortable(container, {
                group: 'teams',
                animation: 150,
                onEnd: function(evt) {
                    const teamId = evt.to.dataset.teamId;
                    const userIds = Array.from(evt.to.children).map(el => el.dataset.userId);
                    updateTeamMembers(teamId, userIds);
                }
            });
        });
    }

    function updateTeamMembers(teamId, userIds) {
        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_team_members',
                team_id: teamId,
                user_ids: userIds,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (!response.success) {
                    alert('Failed to update team members');
                    location.reload();
                }
            }
        });
    }

    // Replace emoji picker handler
    $(document).on('click', '.emoji-picker', function(e) {
        e.preventDefault();
        const button = $(this);
        const input = button.next('.emoji-input');
        
        // Create a contenteditable div to trigger the emoji picker
        const picker = $('<div>')
            .attr('contenteditable', 'true')
            .css({
                position: 'fixed',
                top: '-999px',
                left: '-999px',
                opacity: 0
            })
            .appendTo('body')
            .focus();

        // Handle the input event which fires when an emoji is selected
        picker.on('input', function() {
            const emoji = $(this).text();
            input.val(emoji);
            button.text(emoji || 'Select Emoji');
            picker.remove();
        });

        // Remove the picker when it loses focus
        picker.on('blur', function() {
            setTimeout(() => picker.remove(), 100);
        });
    });

    // Add New Competition button handler
    $('#add-new-competition').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('.competition-form-wrap').slideDown();
        $('html, body').animate({
            scrollTop: $('.competition-form-wrap').offset().top - 50
        }, 500);
    });

    // Cancel button handler
    $('.cancel-form').on('click', function() {
        resetForm();
        $('.competition-form-wrap').slideUp();
    });

    function resetForm() {
        $('.add-competition-form')[0].reset();
        $('#campaign_id').prop('disabled', true);
        $('#pods-container').html('<p>Select a campaign to view available pods.</p>');
        $('#teams-preview').hide();
        $('.teams-container').empty();
        // Reset any emoji buttons to default state
        $('.emoji-picker').text('Select Emoji');
        $('.emoji-input').val('');
    }

    // Form submission handler
    $('.add-competition-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'save_competition');
        formData.append('nonce', kpiDashboard.nonce);

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = window.location.href + 
                        (window.location.search ? '&' : '?') + 
                        'message=success&competition_id=' + response.data;
                } else {
                    alert('Error saving competition: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error saving competition');
            }
        });
    });
});
