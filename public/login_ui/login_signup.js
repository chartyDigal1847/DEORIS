const tabButtons = document.querySelectorAll(".tab-btn");
const loginFormElement = document.getElementById("loginForm");
const signupFormElement = document.getElementById("signupForm");
const authSwitchTextElement = document.getElementById("switchText");
const signupPasswordInput = document.getElementById("signupPassword");
const signupPasswordConfirmInput = document.getElementById("signupConfirm");
const authPageElement = document.querySelector(".auth-page");

const AUTH_MODE = {
  LOGIN: "login",
  SIGNUP: "signup",
};

const SWITCH_TEXT_CONTENT = {
  [AUTH_MODE.LOGIN]: {
    label: "Don't have an account?",
    actionLabel: "Sign Up",
    targetMode: AUTH_MODE.SIGNUP,
  },
  [AUTH_MODE.SIGNUP]: {
    label: "Already have an account?",
    actionLabel: "Login",
    targetMode: AUTH_MODE.LOGIN,
  },
};

function updateAuthSwitchText(mode) {
  const switchContent = SWITCH_TEXT_CONTENT[mode];
  if (!switchContent) {
    return;
  }

  const { label, actionLabel, targetMode } = switchContent;
  authSwitchTextElement.innerHTML = `${label} <button type="button" class="switch-btn" data-switch="${targetMode}">${actionLabel}</button>`;
}

function setAuthMode(mode) {
  if (!Object.values(AUTH_MODE).includes(mode)) {
    return;
  }

  const isLoginMode = mode === AUTH_MODE.LOGIN;

  tabButtons.forEach((tabButtonElement) => {
    tabButtonElement.classList.toggle("is-active", tabButtonElement.dataset.tab === mode);
  });

  loginFormElement.classList.toggle("is-visible", isLoginMode);
  signupFormElement.classList.toggle("is-visible", !isLoginMode);
  updateAuthSwitchText(mode);
}

tabButtons.forEach((tabButtonElement) => {
  tabButtonElement.addEventListener("click", () => {
    setAuthMode(tabButtonElement.dataset.tab);
  });
});

authSwitchTextElement.addEventListener("click", (event) => {
  const switchButton = event.target.closest(".switch-btn");
  if (!switchButton) {
    return;
  }

  const targetMode = switchButton.dataset.switch;
  setAuthMode(targetMode);
});

signupFormElement.addEventListener("submit", (event) => {
  const passwordValue = signupPasswordInput.value;
  const passwordConfirmationValue = signupPasswordConfirmInput.value;

  if (passwordValue !== passwordConfirmationValue) {
    event.preventDefault();
    alert("Passwords do not match.");
  }
});

setAuthMode(authPageElement?.dataset.initialAuthMode || AUTH_MODE.LOGIN);
