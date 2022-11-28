import { css, html, LitElement } from "lit";
import { classMap } from "lit/directives/class-map.js";
import { DtBase } from "@disciple.tools/web-components";

export class DtChurchHealthCircle extends DtBase {
  static get styles() {
    return css`
      .health-circle {
        display: block;
        margin: 3rem auto;
        height: auto;
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 100%;
        border: 3px darkgray dashed;
      }
      .health-circle__grid {
        display: inline-block;
        position: relative;
        height: 100%;
        width: 100%;
        margin-left: auto;
        margin-right: auto;

        --d: 50px; /* image size */
        --rel: 0.5; /* how much extra space we want between images, 1 = one image size */
        --r: calc(0.7 * var(--d) / var(--tan)); /* circle radius */
        --s: calc(2.75 * var(--r));
        position: relative;
        width: var(--s);
        max-width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
      }

      @media (min-width: 420px) {
        .health-circle__grid {
          --r: calc(0.85 * var(--d) / var(--tan)); /* circle radius */
        }
      }

      .health-circle--committed {
        border: 3px #4caf50 solid !important;
      }
      dt-church-health-icon {
        margin: auto;
        position: absolute;
        height: 50px;
        width: 50px;
        border-radius: 100%;
        font-size: 16px;
        color: black;
        text-align: center;
        font-style: italic;
        cursor: pointer;

        position: absolute;
        top: 50%;
        left: 50%;
        margin: calc(-0.5 * var(--d));
        width: var(--d);
        height: var(--d);
        --az: calc(var(--i) * 1turn / var(--m));
        transform: rotate(var(--az)) translate(var(--r))
          rotate(calc(-1 * var(--az)));
      }
    `;
  }

  static get properties() {
    return {
      groupId: { type: Number },
      group: { type: Object, reflect: false },
      settings: { type: Object, reflect: false },
      errorMessage: { type: String, attribute: false },
      missingIcon: { type: String },
    };
  }

  /**
   * Map fields settings as an array and filter out church commitment
   */
  get metrics() {
    const settings = this.settings || [];

    if (!Object.values(settings).length) {
      return [];
    }

    const entries = Object.entries(settings);

    //We don't want to show church commitment in the circle
    return entries.filter(([key, value]) => key !== "church_commitment");
  }

  /**
   * Fetch group data on component load if it's not provided as a property
   */
  connectedCallback() {
    super.connectedCallback();
    this.fetch();
  }

  adoptedCallback() {
    this.distributeItems();
  }

  /**
   * Position the items after the component is rendered
   */
  updated() {
    this.distributeItems();
  }

  /**
   * Fetch the group and settings data if not provided by the server
   */
  async fetch() {
    try {
      const promises = [this.fetchSettings(), this.fetchGroup()];
      let [settings, group] = await Promise.all(promises);
      this.settings = settings;
      this.post = group;
      if (!settings) {
        this.errorMessage = "Error loading settings";
      }
      if (!group) {
        this.errorMessage = "Error loading group";
      }
    } catch (e) {
      console.error(e);
    }
  }

  /**
   * Fetch the group data if it's not already set
   * @returns
   */
  fetchGroup() {
    if (this.group) {
      return Promise.resolve(this.group);
    }
    fetch(`/wp-json/dt-posts/v2/groups/${this.groupId}`).then((response) =>
      response.json()
    );
  }

  /**
   * Fetch the settings data if not already set
   * @returns
   */
  fetchSettings() {
    if (this.settings) {
      return Promise.resolve(this.settings);
    }
    return fetch("/wp-json/dt-posts/v2/groups/settings").then((response) =>
      response.json()
    );
  }

  /**
   * Find a metric by key
   * @param {*} key
   * @returns
   */
  findMetric(key) {
    const metric = this.metrics.find((item) => item.key === key);
    return metric ? metric.value : null;
  }

  /**
   * Render the component
   * @returns
   */
  render() {
    //Show the spinner if we don't have data
    if (!this.group || !this.metrics.length) {
      return html`<dt-spinner></dt-spinner>`;
    }

    //Setup data
    const practicedItems = this.group.health_metrics || [];
    const missingIcon = this.missingIcon
      ? this.missingIcon
      : "/dt-assets/images/groups/missing.svg";

    //Show the error message if we have one
    if (this.errorMessage) {
      html`<dt-alert type="error">${this.errorMessage}</dt-alert>`;
    }

    //Render the group circle
    return html`
      <div>
        <div
          class=${classMap({
            "health-circle": true,
            "health-circle--committed":
              practicedItems.indexOf("church_commitment") !== -1,
          })}
        >
          <div class="health-circle__grid">
            ${this.metrics.map(
              ([key, metric], index) =>
                html`<dt-church-health-icon
                  key="${key}"
                  .group="${this.group}"
                  .metric=${metric}
                  .active=${practicedItems.indexOf(key) !== -1}
                  .style="--i: ${index + 1}"
                >
                </dt-church-health-icon>`
            )}
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Dynamically distribute items in Church Health Circle
   * according to amount of health metric elements
   */
  distributeItems() {
    const container = this.renderRoot.querySelector(".health-circle__grid");
    const items = container.querySelectorAll("dt-church-health-icon");

    let n_items = items.length;
    let m = n_items; /* how many are ON the circle */
    let tan = Math.tan(Math.PI / m); /* tangent of half the base angle */

    container.style.setProperty("--m", m);
    container.style.setProperty("--tan", +tan.toFixed(2));
  }
}

class DtChurchHealthIcon extends LitElement {
  static get styles() {
    return css`
      root {
        display: block;
      }
      .health-item img {
        height: 50px;
        width: 50px;
        filter: grayscale(1) opacity(0.75);
      }
      .health-item--active img {
        filter: none !important;
      }
    `;
  }

  static get properties() {
    return {
      key: { type: String },
      metric: { type: Object },
      group: { type: Object },
      active: { type: Boolean, reflect: true },
    };
  }

  render() {
    const { key, metric, active } = this;

    return html`<div
      class=${classMap({
        "health-item": true,
        "health-item--active": active,
      })}
      title="${metric.description}"
      @click="${this._handleClick}"
    >
      <img src="${metric.icon ? metric.icon : missingIcon}" />
    </div>`;
  }

  async _handleClick() {
    const active = !this.active;
    this.active = active;
    const payload = {
      health_metrics: {
        values: [
          {
            value: this.key,
            delete: !active,
          },
        ],
      },
    };
    try {
      API.update_post("groups", this.group.ID, payload);
      if (active) {
        this.group.health_metrics.push(this.key);
      } else {
        this.group.health_metrics.pop(this.key);
      }
    } catch (err) {
      console.log(err);
    }
  }
}

window.customElements.define("dt-church-health-icon", DtChurchHealthIcon);
window.customElements.define("dt-church-health-circle", DtChurchHealthCircle);
