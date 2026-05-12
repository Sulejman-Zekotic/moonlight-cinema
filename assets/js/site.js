document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const shell = document.querySelector('[data-nav-shell]');
  const closeTriggers = document.querySelectorAll('[data-nav-close]');
  const navLinks = document.querySelectorAll('.mc-site-nav__link');
  const suspiciousPattern = /[\u00C3\u00C5\u00C4\u00E2\uFFFD]/;
  const textDecoder = typeof TextDecoder !== 'undefined' ? new TextDecoder('utf-8') : null;
  const attributeNames = ['placeholder', 'aria-label', 'title'];

  function suspiciousCount(value) {
    return (value.match(/[\u00C3\u00C5\u00C4\u00E2\uFFFD]/g) || []).length;
  }

  function decodeLatin1Utf8(value) {
    if (!textDecoder || !value) {
      return value;
    }

    const bytes = Uint8Array.from([...value].map((char) => char.charCodeAt(0) & 0xff));
    return textDecoder.decode(bytes);
  }

  function repairText(value) {
    if (typeof value !== 'string' || !value || !suspiciousPattern.test(value)) {
      return value;
    }

    let nextValue = value;

    for (let index = 0; index < 2; index += 1) {
      try {
        const candidate = decodeLatin1Utf8(nextValue);
        if (!candidate || candidate === nextValue) {
          break;
        }

        if (suspiciousCount(candidate) <= suspiciousCount(nextValue)) {
          nextValue = candidate;
        } else {
          break;
        }
      } catch {
        break;
      }
    }

    return nextValue;
  }

  function normalizeTextNode(node) {
    const repaired = repairText(node.nodeValue || '');
    if (repaired !== node.nodeValue) {
      node.nodeValue = repaired;
    }
  }

  function normalizeElement(element) {
    if (!element || ['SCRIPT', 'STYLE'].includes(element.tagName)) {
      return;
    }

    attributeNames.forEach((attributeName) => {
      const currentValue = element.getAttribute(attributeName);
      if (!currentValue) {
        return;
      }

      const repaired = repairText(currentValue);
      if (repaired !== currentValue) {
        element.setAttribute(attributeName, repaired);
      }
    });

    element.childNodes.forEach((childNode) => {
      if (childNode.nodeType === Node.TEXT_NODE) {
        normalizeTextNode(childNode);
        return;
      }

      if (childNode.nodeType === Node.ELEMENT_NODE) {
        normalizeElement(childNode);
      }
    });
  }

  function normalizeTree(root) {
    if (!root) {
      return;
    }

    if (root.nodeType === Node.TEXT_NODE) {
      normalizeTextNode(root);
      return;
    }

    if (root.nodeType === Node.ELEMENT_NODE) {
      normalizeElement(root);
    }
  }

  window.mcNormalizeText = normalizeTree;
  normalizeTree(document.body);

  if (document.body) {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'characterData') {
          normalizeTextNode(mutation.target);
          return;
        }

        if (mutation.type === 'attributes' && mutation.target.nodeType === Node.ELEMENT_NODE) {
          normalizeElement(mutation.target);
          return;
        }

        mutation.addedNodes.forEach((node) => normalizeTree(node));
      });
    });

    observer.observe(document.body, {
      subtree: true,
      childList: true,
      characterData: true,
      attributes: true,
      attributeFilter: attributeNames,
    });
  }

  if (!toggle || !shell) {
    return;
  }

  function closeMenu() {
    shell.classList.remove('is-open');
    toggle.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mc-nav-open');
  }

  function openMenu() {
    shell.classList.add('is-open');
    toggle.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('mc-nav-open');
  }

  toggle.addEventListener('click', () => {
    if (shell.classList.contains('is-open')) {
      closeMenu();
      return;
    }

    openMenu();
  });

  closeTriggers.forEach((trigger) => {
    trigger.addEventListener('click', closeMenu);
  });

  navLinks.forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 760) {
      closeMenu();
    }
  });
});
