export default class MenuScroll {
  static TRANSITION = 'translate3d(0, -100%, 0)';
  static ADMINBAR_ID = 'wpadminbar';
  static ERROR_MISSING_ELEMENT = 'Element missing in DOM';

  constructor(elem) {
    this.elem = document.querySelector(elem);

    if (!this.elem) {
      return;
    }

    this.scrollTop = window.scrollY;
    this.canTransition = true;

    window.addEventListener('scroll', this.manageScroll.bind(this));

    this.elem.addEventListener('mouseenter', () => {
      this.canTransition = false;
    });

    this.elem.addEventListener('mouseleave', () => {
      this.canTransition = true;
    });

    const footnotes = document.querySelectorAll('a[href^="#"][href$="link"], a[href^="#"][id$="link"]');

    for (let link of footnotes) {
      link.addEventListener('click', this.handleFootnotes.bind(this));
    }
  }

  manageScroll() {
    window.requestAnimationFrame(() => {
      if (!this.canTransition) return;

      if (window.scrollY < this.scrollTop && window.scrollY > this.elem.offsetHeight * 2) {
        this.hideMenu();
      } else {
        this.showMenu();
      }

      this.scrollTop = window.scrollY;
    });
  }

  hideMenu() {
    const adminBar = document.getElementById(MenuScroll.ADMINBAR_ID);
    let transition = MenuScroll.TRANSITION;

    if (adminBar) {
      transition = `translate3d(0, -${this.elem.offsetHeight + adminBar.offsetHeight}px, 0)`;
    }

    this.elem.style.transform = transition;
  }

  showMenu() {
    this.elem.removeAttribute('style');
  }

  handleFootnotes() {
    this.hideMenu();
    this.canTransition = false;

    // @todo: Find better solution.
    window.setTimeout(() => {
      this.canTransition = true;
    }, 2000);
  }
}