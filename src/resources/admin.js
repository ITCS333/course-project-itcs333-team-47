/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/// --- Global Data Store ---
let resources = [];

// --- Element Selections ---
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Functions ---

/**
 * Create a table row (<tr>) for a resource object
 */
function createResourceRow(resource) {
    const tr = document.createElement('tr');

    // Title TD
    const titleTd = document.createElement('td');
    titleTd.textContent = resource.title;
    tr.appendChild(titleTd);

    // Description TD
    const descTd = document.createElement('td');
    descTd.textContent = resource.description;
    tr.appendChild(descTd);

    // Actions TD
    const actionsTd = document.createElement('td');

    // Edit button
    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.classList.add('edit-btn');
    editBtn.setAttribute('data-id', resource.id);
    actionsTd.appendChild(editBtn);

    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.classList.add('delete-btn');
    deleteBtn.setAttribute('data-id', resource.id);
    actionsTd.appendChild(deleteBtn);

    tr.appendChild(actionsTd);

    return tr;
}

/**
 * Render the resources table
 */
function renderTable() {
    // Clear the table body
    resourcesTableBody.innerHTML = '';

    // Loop through resources and append rows
    resources.forEach(resource => {
        const tr = createResourceRow(resource);
        resourcesTableBody.appendChild(tr);
    });
}

/**
 * Handle Add Resource form submission
 */
function handleAddResource(event) {
    event.preventDefault();

    // Get input values
    const title = document.querySelector('#resource-title').value.trim();
    const description = document.querySelector('#resource-description').value.trim();
    const link = document.querySelector('#resource-link').value.trim();

    if (!title || !link) return; // required fields

    // Create a new resource object
    const newResource = {
        id: `res_${Date.now()}`,
        title,
        description,
        link
    };

    // Add to global array
    resources.push(newResource);

    // Refresh table
    renderTable();

    // Reset form
    resourceForm.reset();
}

/**
 * Handle clicks on the resources table (delegation)
 */
function handleTableClick(event) {
    const target = event.target;

    // Delete functionality
    if (target.classList.contains('delete-btn')) {
        const id = target.getAttribute('data-id');

        // Filter out the deleted resource
        resources = resources.filter(res => res.id !== id);

        // Refresh table
        renderTable();
    }

    // Optional: Edit functionality could be implemented here
}

/**
 * Load resources from JSON and initialize event listeners
 */
async function loadAndInitialize() {
    try {
        // Fetch the resources JSON
        const response = await fetch('resources.json');
        const data = await response.json();

        // Store in global array
        resources = data;

        // Initial render
        renderTable();

        // Event listeners
        resourceForm.addEventListener('submit', handleAddResource);
        resourcesTableBody.addEventListener('click', handleTableClick);
    } catch (error) {
        console.error('Error loading resources:', error);
    }
}

// --- Initial Page Load ---
loadAndInitialize();
