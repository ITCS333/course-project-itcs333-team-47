/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/
// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

/**
 * Create an <article> element for a resource
 */
function createResourceArticle(resource) {
    const article = document.createElement('article');

    // Title
    const titleEl = document.createElement('h2');
    titleEl.textContent = resource.title;
    article.appendChild(titleEl);

    // Description
    const descEl = document.createElement('p');
    descEl.textContent = resource.description;
    article.appendChild(descEl);

    // Link to details page
    const linkEl = document.createElement('a');
    linkEl.textContent = 'View Resource & Discussion';
    linkEl.href = `details.html?id=${resource.id}`;
    linkEl.target = '_blank';
    article.appendChild(linkEl);

    return article;
}

/**
 * Load resources from JSON and render them
 */
async function loadResources() {
    try {
        const response = await fetch('resources.json');
        const resources = await response.json();

        // Clear existing content
        listSection.innerHTML = '';

        // Loop through and append articles
        resources.forEach(resource => {
            const article = createResourceArticle(resource);
            listSection.appendChild(article);
        });
    } catch (error) {
        console.error('Error loading resources:', error);
        listSection.innerHTML = '<p>Failed to load resources.</p>';
    }
}

// --- Initial Page Load ---
loadResources();
