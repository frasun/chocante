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
  }

  manageScroll() {
    window.requestAnimationFrame(() => {
      if (!this.canTransition) return;

      if (window.scrollY > this.scrollTop && window.scrollY > this.elem.offsetHeight) {
        const adminBar = document.getElementById(MenuScroll.ADMINBAR_ID);
        let transition = MenuScroll.TRANSITION;

        if (adminBar) {
          transition = `translate3d(0, -${this.elem.offsetHeight + adminBar.offsetHeight}px, 0)`;
        }

        this.elem.style.transform = transition;
      } else if (window.scrollY < this.scrollTop) {
        this.elem.removeAttribute('style');
      }

      this.scrollTop = window.scrollY;
    });
  }
}