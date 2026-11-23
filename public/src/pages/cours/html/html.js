document.addEventListener('DOMContentLoaded', async function() {
  try {
      await addMarkdown('Altherneum/.github', 'note/Code/Web/HTML/learning.md', false);
      await addMarkdown('Altherneum/.github', 'note/Code/Web/HTML/liste.md', false);
      await addMarkdown('Altherneum/.github', 'note/Code/Web/HTML/boilerplate.md', false);
  } catch(error) {
    console.error(error);
  }
});