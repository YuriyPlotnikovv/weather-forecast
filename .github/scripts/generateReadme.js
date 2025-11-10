const fs = require('fs');
const path = require('path');

const projectJsonPath = path.join(process.env.GITHUB_WORKSPACE, '.info', 'project.json');
const templatePath = path.join(process.env.GITHUB_WORKSPACE, '.info', 'README-template.md');
const readmePath = path.join(process.env.GITHUB_WORKSPACE, 'README.md');

function formatStringList(items) {
  if (!Array.isArray(items) || items.length === 0) return '';
  return items.map(item => `- ${item}`).join('\n');
}

function fillTemplate(template, vars) {
  return template.replace(/{{\s*([\w-]+)\s*}}/g, (_, key) => vars[key] || '');
}

function generateReadme() {
  if (!fs.existsSync(projectJsonPath)) {
    console.error('project.json не найден');
    process.exit(1);
  }
  if (!fs.existsSync(templatePath)) {
    console.error('Шаблон README-template.md не найден');
    process.exit(1);
  }

  const rawData = fs.readFileSync(projectJsonPath, 'utf8');
  const data = JSON.parse(rawData);

  const template = fs.readFileSync(templatePath, 'utf8');

  const posterImg = data.image ? `<img src=".info/${data.image}" alt="Poster" width="600" />` : '';

  const vars = {
    'title-en': data.title.en || '',
    'title-ru': data.title.ru || '',
    'textFirst-en': data.textFirst.en || '',
    'textFirst-ru': data.textFirst.ru || '',
    'textSecond-en': data.textSecond.en || '',
    'textSecond-ru': data.textSecond.ru || '',
    'functionality-en': formatStringList(data.functionality.en),
    'functionality-ru': formatStringList(data.functionality.ru),
    'pages-en': formatStringList(data.pages.en),
    'pages-ru': formatStringList(data.pages.ru),
    'notImplemented-en': formatStringList(data.notImplemented.en),
    'notImplemented-ru': formatStringList(data.notImplemented.ru),
    'deploy': data.deploy || '',
    'poster-img': posterImg
  };

  const readme = fillTemplate(template, vars);

  fs.writeFileSync(readmePath, readme, 'utf8');
  console.log('README.md сгенерирован из шаблона');
}

generateReadme();
