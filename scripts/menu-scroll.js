export default class MenuScroll {
  static TRANSITION = 'translate3d(0, -100%, 0)';
  static ADMINBAR_ID = 'wpadminbar';
  static ERROR_MISSING_ELEMENT = 'Element missing in DOM';

  constructor(elem) {
    this.elem = document.querySelector(elem);
    this.scrollTop = window.scrollY;
    this.canTransition = true;

    if (!this.elem) {
      throw new Error(MenuScroll.ERROR_MISSING_ELEMENT)
    }

    window.addEventListener('scroll', this.manageScroll.bind(this));

    this.elem.addEventListener('mouseenter', () => {
      this.canTransition = false;
    });

    this.elem.addEventListener('mouseleave', () => {
      this.canTransition = true;
    });

    const pageLinks = document.querySelectorAll('a[href^="#"]');

    for (let link of pageLinks) {
      link.addEventListener('click', this.handlePageLink.bind(this));
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

  handlePageLink() {
    this.hideMenu();
    this.canTransition = false;

    // @todo: Find better solution.
    window.setTimeout(() => {
      this.canTransition = true;
    }, 2000);
  }
}