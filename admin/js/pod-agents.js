jQuery(document).ready(function($) {
    // Handle Manage Agents button click
    $('.manage-agents').on('click', function() {
        const podId = $(this).data('id');
        const podName = $(this).data('name');
        loadPodAgents(podId, podName);
        $('#pod-agents-dialog').dialog('open');
    });

    // Handle pod row toggle
    $(document).on('click', '.pod-main', function() {
        const $podRow = $(this).closest('.pod-row');
        const $agentsSection = $podRow.find('.pod-agents-section');
        
        if (!$agentsSection.hasClass('initialized')) {
            const podId = $podRow.data('pod-id');
            const podName = $podRow.data('pod-name');
            loadPodAgents(podId, podName, $agentsSection);
            $agentsSection.addClass('initialized');
        }
        
        $podRow.toggleClass('expanded');
        $agentsSection.slideToggle();
    });

    // Initialize dialog
    $('#pod-agents-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        title: 'Manage Pod Agents',
        buttons: {
            Save: function() {
                savePodAgents();
            },
            Cancel: function() {
                $(this).dialog('close');
            }
        }
    });

    function loadPodAgents(podId, podName, $container = null) {
        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pod_agents',
                pod_id: podId,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    if ($container) {
                        populateAgentsSection(podId, podName, response.data, $container);
                    } else {
                        populateAgentsDialog(podId, podName, response.data);
                    }
                }
            }
        });
    }

    function populateAgentsDialog(podId, podName, data) {
        const $dialog = $('#pod-agents-dialog');
        const $usersList = $dialog.find('.users-list');
        const $agentsList = $dialog.find('.agents-list');
        
        $usersList.empty();
        $agentsList.empty();
        
        $dialog.find('.pod-name').text(podName);
        $dialog.data('pod-id', podId);

        // Populate available users
        data.all_users.forEach(user => {
            const isAssigned = data.pod_users.some(podUser => parseInt(podUser.user_id) === parseInt(user.ID));
            if (!isAssigned) {
                $usersList.append(`
                    <div class="user-item" data-user-id="${user.ID}">
                        <img src="${user.avatar_url}" class="user-avatar" alt="Avatar">
                        <div class="user-info">
                            <span class="user-name">${user.first_name} ${user.last_name}</span>
                            <span class="user-email">${user.user_email}</span>
                        </div>
                        <button class="add-to-pod button button-small">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                    </div>
                `);
            }
        });

        // Populate assigned agents
        data.pod_users.forEach(agent => {
            $agentsList.append(`
                <div class="agent-item" data-user-id="${agent.user_id}">
                    <img src="${agent.avatar_url}" class="user-avatar" alt="Avatar">
                    <div class="user-info">
                        <span class="user-name">${agent.first_name} ${agent.last_name}</span>
                        <span class="user-email">${agent.user_email}</span>
                    </div>
                    <select class="agent-role">
                        <option value="agent" ${agent.role === 'agent' ? 'selected' : ''}>Agent</option>
                        <option value="leader" ${agent.role === 'leader' ? 'selected' : ''}>Team Leader</option>
                        <option value="manager" ${agent.role === 'manager' ? 'selected' : ''}>Pod Manager</option>
                    </select>
                    <button class="remove-from-pod button button-small">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            `);
        });
    }

    function populateAgentsSection(podId, podName, data, $container) {
        $container.html(`
            <div class="pod-agents-wrapper">
                <div class="available-users">
                    <h3>Available Users</h3>
                    <input type="text" class="filter-users" placeholder="Filter users...">
                    <div class="users-list"></div>
                </div>
                <div class="assigned-agents">
                    <h3>Pod Agents</h3>
                    <div class="agents-list"></div>
                </div>
            </div>
            <div class="pod-agents-actions">
                <span class="save-status"></span>
                <button class="button button-primary save-pod-agents">Save Changes</button>
            </div>
        `);

        const $usersList = $container.find('.users-list');
        const $agentsList = $container.find('.agents-list');

        // Populate available users
        data.all_users.forEach(user => {
            const isAssigned = data.pod_users.some(podUser => parseInt(podUser.user_id) === parseInt(user.ID));
            if (!isAssigned) {
                $usersList.append(`
                    <div class="user-item" data-user-id="${user.ID}">
                        <img src="${user.avatar_url}" class="user-avatar" alt="Avatar">
                        <div class="user-info">
                            <span class="user-name">${user.first_name} ${user.last_name}</span>
                            <span class="user-email">${user.user_email}</span>
                        </div>
                        <button class="add-to-pod button button-small">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                    </div>
                `);
            }
        });

        // Populate assigned agents
        data.pod_users.forEach(agent => {
            $agentsList.append(`
                <div class="agent-item" data-user-id="${agent.user_id}">
                    <img src="${agent.avatar_url}" class="user-avatar" alt="Avatar">
                    <div class="user-info">
                        <span class="user-name">${agent.first_name} ${agent.last_name}</span>
                        <span class="user-email">${agent.user_email}</span>
                    </div>
                    <select class="agent-role">
                        <option value="agent" ${agent.role === 'agent' ? 'selected' : ''}>Agent</option>
                        <option value="leader" ${agent.role === 'leader' ? 'selected' : ''}>Team Leader</option>
                        <option value="manager" ${agent.role === 'manager' ? 'selected' : ''}>Pod Manager</option>
                    </select>
                    <button class="remove-from-pod button button-small">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            `);
        });
    }

    // Filter users
    $('#filter-users').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            const userName = $(this).find('.user-name').text().toLowerCase();
            $(this).toggle(userName.includes(searchTerm));
        });
    });

    // Add user to pod
    $(document).on('click', '.add-to-pod', function() {
        const $userItem = $(this).closest('.user-item');
        const userId = $userItem.data('user-id');
        const userName = $userItem.find('.user-name').text();
        
        // Move to agents list
        $('.agents-list').append(`
            <div class="agent-item" data-user-id="${userId}">
                <span class="agent-name">${userName}</span>
                <select class="agent-role">
                    <option value="agent">Agent</option>
                    <option value="leader">Team Leader</option>
                    <option value="manager">Pod Manager</option>
                </select>
                <span class="agent-actions">
                    <button class="edit-user button button-small">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button class="remove-from-pod button button-small">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </span>
            </div>
        `);
        
        $userItem.remove();
    });

    // Remove agent from pod
    $(document).on('click', '.remove-from-pod', function() {
        const $agentItem = $(this).closest('.agent-item');
        const userId = $agentItem.data('user-id');
        const userName = $agentItem.find('.agent-name').text();
        
        // Move back to users list
        $('.users-list').append(`
            <div class="user-item" data-user-id="${userId}">
                <span class="user-name">${userName}</span>
                <span class="user-email"></span>
                <span class="user-actions">
                    <button class="add-to-pod button button-small">
                        <span class="dashicons dashicons-plus"></span>
                    </button>
                </span>
            </div>
        `);
        
        $agentItem.remove();
    });

    function savePodAgents() {
        const $dialog = $('#pod-agents-dialog');
        const podId = $dialog.data('pod-id');
        const agents = [];

        $dialog.find('.agent-item').each(function() {
            agents.push({
                user_id: $(this).data('user-id'),
                role: $(this).find('.agent-role').val()
            });
        });

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_pod_agents',
                pod_id: podId,
                agents: agents,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#pod-agents-dialog').dialog('close');
                    // Optionally refresh the page or update UI
                } else {
                    alert('Failed to save pod agents');
                }
            }
        });
    }

    // Save pod agents
    $('.save-pod-agents').on('click', function() {
        const podId = $('#pod-agents-dialog').data('pod-id');
        const agents = [];
        
        $('.agent-item').each(function() {
            agents.push({
                user_id: $(this).data('user-id'),
                role: $(this).find('.agent-role').val()
            });
        });

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_pod_agents',
                nonce: kpiDashboard.nonce,
                pod_id: podId,
                agents: agents
            },
            success: function(response) {
                if (response.success) {
                    $('#pod-agents-dialog').dialog('close');
                    // Refresh the dialog to show updated data
                    loadPodAgents(podId);
                } else {
                    alert('Failed to save pod agents');
                }
            }
        });
    });

    // Handle saving pod agents
    $(document).on('click', '.save-pod-agents', function() {
        const $button = $(this);
        const $section = $button.closest('.pod-agents-section');
        const $status = $section.find('.save-status');
        const podId = $button.closest('.pod-row').data('pod-id');
        
        $button.prop('disabled', true);
        $status.removeClass('success error').text('Saving...').show();

        const agents = [];
        $section.find('.agent-item').each(function() {
            agents.push({
                user_id: $(this).data('user-id'),
                role: $(this).find('.agent-role').val()
            });
        });

        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_pod_agents',
                pod_id: podId,
                agents: agents,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('Changes saved successfully!');
                    setTimeout(() => {
                        $status.fadeOut();
                    }, 3000);
                } else {
                    $status.addClass('error').text('Error saving changes');
                }
            },
            error: function() {
                $status.addClass('error').text('Error saving changes');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Track changes to show save button
    $(document).on('change', '.agent-role', function() {
        const $section = $(this).closest('.pod-agents-section');
        $section.find('.save-pod-agents').addClass('button-primary');
        $section.find('.save-status').hide();
    });

    // Update UI after adding/removing agents
    $(document).on('click', '.add-to-pod, .remove-from-pod', function() {
        const $section = $(this).closest('.pod-agents-section');
        $section.find('.save-pod-agents').addClass('button-primary');
        $section.find('.save-status').hide();
    });

    // Handle checkbox changes to enable/disable role select
    $(document).on('change', '.agent-select', function() {
        const $roleSelect = $(this).closest('.agent-item').find('.agent-role');
        $roleSelect.prop('disabled', !this.checked);
    });

    // Create New User Dialog
    $(document).on('click', '#create-new-user', function() {
        const $createDialog = $(`
            <div id="create-user-dialog" title="Create New User">
                <form id="create-user-form">
                    <p>
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </p>
                    <p>
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </p>
                    <p>
                        <label>First Name</label>
                        <input type="text" name="first_name">
                    </p>
                    <p>
                        <label>Last Name</label>
                        <input type="text" name="last_name">
                    </p>
                    <p>
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </p>
                    <p>
                        <label>Role</label>
                        <select name="role">
                            <option value="subscriber">Subscriber</option>
                            <option value="contributor">Contributor</option>
                            <option value="author">Author</option>
                        </select>
                    </p>
                </form>
            </div>
        `).dialog({
            modal: true,
            width: 400,
            buttons: {
                "Create": function() {
                    const $form = $('#create-user-form');
                    const formData = new FormData($form[0]);
                    
                    $.ajax({
                        url: kpiDashboard.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'create_pod_user',
                            formData: Object.fromEntries(formData),
                            nonce: kpiDashboard.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                loadPodAgents($('#pod-agents-dialog').data('pod-id'));
                                $createDialog.dialog('close');
                            } else {
                                alert(response.data || 'Failed to create user');
                            }
                        }
                    });
                },
                "Cancel": function() {
                    $(this).dialog('close');
                }
            }
        });
    });

    // Handle edit user
    $(document).on('click', '.edit-user', function() {
        const $agentItem = $(this).closest('.agent-item');
        const userId = $agentItem.data('user-id');
        
        $.ajax({
            url: kpiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_details',
                user_id: userId,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    showEditUserDialog(response.data);
                }
            }
        });
    });
});
