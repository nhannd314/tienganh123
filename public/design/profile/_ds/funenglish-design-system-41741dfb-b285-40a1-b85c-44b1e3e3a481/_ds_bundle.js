/* @ds-bundle: {"format":4,"namespace":"FunEnglishDesignSystem_41741d","components":[{"name":"Badge","sourcePath":"components/core/Badge.jsx"},{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Card","sourcePath":"components/core/Card.jsx"},{"name":"Icon","sourcePath":"components/core/Icon.jsx"},{"name":"IconButton","sourcePath":"components/core/IconButton.jsx"},{"name":"Pill","sourcePath":"components/core/Pill.jsx"},{"name":"Alert","sourcePath":"components/feedback/Alert.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"SearchInput","sourcePath":"components/forms/SearchInput.jsx"},{"name":"TextInput","sourcePath":"components/forms/TextInput.jsx"},{"name":"AnswerCard","sourcePath":"components/learning/AnswerCard.jsx"},{"name":"AudioButton","sourcePath":"components/learning/AudioButton.jsx"},{"name":"Flashcard","sourcePath":"components/learning/Flashcard.jsx"},{"name":"LessonCard","sourcePath":"components/learning/LessonCard.jsx"},{"name":"ProgressBar","sourcePath":"components/learning/ProgressBar.jsx"},{"name":"ProgressRing","sourcePath":"components/learning/ProgressRing.jsx"},{"name":"SpeechBubble","sourcePath":"components/learning/SpeechBubble.jsx"},{"name":"StatRow","sourcePath":"components/learning/StatRow.jsx"},{"name":"TipCard","sourcePath":"components/learning/TipCard.jsx"},{"name":"WordPrompt","sourcePath":"components/learning/WordPrompt.jsx"},{"name":"Breadcrumbs","sourcePath":"components/navigation/Breadcrumbs.jsx"},{"name":"NavBar","sourcePath":"components/navigation/NavBar.jsx"},{"name":"StepTabs","sourcePath":"components/navigation/StepTabs.jsx"}],"sourceHashes":{"components/core/Badge.jsx":"ec8e1b880f0c","components/core/Button.jsx":"7820e8fbdd12","components/core/Card.jsx":"a5b13e8e04b0","components/core/Icon.jsx":"e3afe527a1f8","components/core/IconButton.jsx":"2231395479e2","components/core/Pill.jsx":"834b9c53a762","components/feedback/Alert.jsx":"30001efe76fc","components/forms/Checkbox.jsx":"bde9dced41e6","components/forms/SearchInput.jsx":"683e7de19e90","components/forms/TextInput.jsx":"e88048c3d806","components/learning/AnswerCard.jsx":"7728b5056517","components/learning/AudioButton.jsx":"9b5ea592107e","components/learning/Flashcard.jsx":"ac3bcca2e935","components/learning/LessonCard.jsx":"59abc392c3e5","components/learning/ProgressBar.jsx":"36998c2a9bbf","components/learning/ProgressRing.jsx":"42ffb34bbddc","components/learning/SpeechBubble.jsx":"33ed5511ee53","components/learning/StatRow.jsx":"f838670140d2","components/learning/TipCard.jsx":"cde161a35ccc","components/learning/WordPrompt.jsx":"474b0d4772bf","components/navigation/Breadcrumbs.jsx":"5ed98bf24a40","components/navigation/NavBar.jsx":"1a11693a209f","components/navigation/StepTabs.jsx":"326439251718","ui_kits/website/App.jsx":"c70e313c7ed2","ui_kits/website/ProgressPanel.jsx":"1f0451cdef7f","ui_kits/website/QuizPanel.jsx":"630386941bfd","ui_kits/website/data.js":"0f2ccbbd406b"},"inlinedExternals":[],"unexposedExports":[{"name":"buttonSizes","sourcePath":"components/core/Button.jsx"},{"name":"buttonStyle","sourcePath":"components/core/Button.jsx"},{"name":"buttonVariants","sourcePath":"components/core/Button.jsx"},{"name":"useInteractive","sourcePath":"components/core/Button.jsx"}]} */

(() => {

const __ds_ns = (window.FunEnglishDesignSystem_41741d = window.FunEnglishDesignSystem_41741d || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/core/Badge.jsx
try { (() => {
function Badge({
  count,
  children,
  inset
}) {
  const o = inset ? 'var(--space-2)' : 'calc(var(--space-1) * -1)';
  return /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      display: 'inline-flex'
    }
  }, children, count > 0 && /*#__PURE__*/React.createElement("span", {
    "aria-label": count + ' thông báo mới',
    style: {
      position: 'absolute',
      top: o,
      right: o,
      minWidth: 'var(--space-5)',
      height: 'var(--space-5)',
      padding: '0 var(--space-1)',
      borderRadius: 'var(--radius-pill)',
      background: 'var(--danger)',
      color: 'var(--text-on-danger)',
      font: 'var(--fw-semibold) var(--fs-xs)/var(--lh-none) var(--font-sans)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      border: 'var(--border-width) solid var(--white)',
      pointerEvents: 'none'
    }
  }, count > 9 ? '9+' : count));
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Badge.jsx", error: String((e && e.message) || e) }); }

// components/core/Card.jsx
try { (() => {
const PAD = {
  none: 0,
  sm: 'var(--space-4)',
  md: 'var(--space-6)',
  lg: 'var(--space-9)'
};
function Card({
  children,
  padding = 'md',
  tone = 'default',
  style
}) {
  const bg = {
    default: 'var(--surface-card)',
    accent: 'var(--surface-quiz)',
    info: 'var(--info-bg)'
  }[tone] || 'var(--surface-card)';
  const raised = tone === 'default';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: bg,
      borderRadius: 'var(--radius-lg)',
      boxShadow: raised ? 'var(--shadow-card)' : 'none',
      border: raised ? 'var(--border-width-thin) solid var(--border-subtle)' : 'none',
      padding: PAD[padding] ?? padding,
      ...style
    }
  }, children);
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Card.jsx", error: String((e && e.message) || e) }); }

// components/core/Icon.jsx
try { (() => {
const SZ = {
  sm: 'var(--icon-sm)',
  md: 'var(--icon-md)',
  lg: 'var(--icon-lg)',
  xl: 'var(--icon-xl)'
};
function Icon({
  name,
  size = 'md',
  color = 'currentColor',
  spin,
  label,
  style
}) {
  return /*#__PURE__*/React.createElement("i", {
    className: 'icon-' + name,
    "aria-hidden": label ? undefined : 'true',
    role: label ? 'img' : undefined,
    "aria-label": label,
    "data-fe-spin": spin ? '' : undefined,
    style: {
      fontSize: SZ[size] || size,
      lineHeight: 'var(--lh-none)',
      color,
      display: 'inline-flex',
      flex: 'none',
      animation: spin ? 'fe-spin 1s linear infinite' : undefined,
      ...style
    }
  });
}
Object.assign(__ds_scope, { Icon });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Icon.jsx", error: String((e && e.message) || e) }); }

// components/core/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function useInteractive(disabled) {
  const [h, setH] = React.useState(false),
    [p, setP] = React.useState(false);
  return [{
    hover: !disabled && h,
    press: !disabled && p
  }, {
    onMouseEnter: () => setH(true),
    onMouseLeave: () => {
      setH(false);
      setP(false);
    },
    onMouseDown: () => setP(true),
    onMouseUp: () => setP(false)
  }];
}
const buttonVariants = {
  primary: {
    bg: 'var(--primary)',
    fg: 'var(--text-on-primary)',
    bd: 'var(--primary)',
    edge: 'var(--primary-strong)'
  },
  secondary: {
    bg: 'var(--secondary)',
    fg: 'var(--text-on-secondary)',
    bd: 'var(--secondary)',
    edge: 'var(--secondary-strong)'
  },
  accent: {
    bg: 'var(--accent)',
    fg: 'var(--text-on-accent)',
    bd: 'var(--accent)',
    edge: 'var(--accent-strong)'
  },
  outline: {
    bg: 'var(--surface-card)',
    bgHover: 'var(--primary-bg)',
    fg: 'var(--primary)',
    bd: 'var(--primary-border)',
    edge: 'var(--primary-border)',
    edgeOnBorder: true
  },
  soft: {
    bg: 'var(--primary-bg-hover)',
    fg: 'var(--primary-text)',
    bd: 'var(--primary-bg-hover)',
    edge: 'var(--primary-border)'
  },
  ghost: {
    bg: 'transparent',
    bgHover: 'var(--primary-bg)',
    fg: 'var(--primary)',
    bd: 'transparent'
  }
};
const buttonSizes = {
  sm: {
    h: 'var(--control-h-sm)',
    px: 'var(--space-4)',
    font: 'var(--type-label)',
    gap: 'var(--space-2)',
    icon: 'md',
    radius: 'var(--radius-sm)'
  },
  md: {
    h: 'var(--control-h-md)',
    px: 'var(--space-6)',
    font: 'var(--type-label-lg)',
    gap: 'var(--space-2)',
    icon: 'md',
    radius: 'var(--radius-md)'
  },
  lg: {
    h: 'var(--control-h-lg)',
    px: 'var(--space-7)',
    font: 'var(--type-label-xl)',
    gap: 'var(--space-3)',
    icon: 'lg',
    radius: 'var(--radius-md)'
  }
};
function buttonStyle(variant, size, st) {
  const v = buttonVariants[variant] || buttonVariants.primary,
    s = buttonSizes[size] || buttonSizes.md,
    d = st.disabled;
  const lift = v.edge && !d ? st.press ? 'pressed' : 'rest' : null;
  return {
    height: s.h,
    minWidth: s.h,
    borderRadius: s.radius,
    borderWidth: 'var(--border-width)',
    borderStyle: 'solid',
    borderColor: d ? 'var(--border-disabled)' : v.bd,
    background: d ? 'var(--surface-disabled)' : st.hover && v.bgHover ? v.bgHover : v.bg,
    color: d ? 'var(--text-disabled)' : v.fg,
    // outline: the 2px border already shows the edge colour, so subtract it to keep the visible edge equal to filled variants
    boxShadow: lift ? '0 ' + (v.edgeOnBorder ? 'max(0px, calc(var(--button-edge' + (lift === 'rest' ? '' : '-' + lift) + ') - var(--border-width)))' : 'var(--button-edge' + (lift === 'rest' ? '' : '-' + lift) + ')') + ' 0 ' + v.edge : 'none',
    transform: lift === 'pressed' ? 'translateY(calc(var(--button-edge) - var(--button-edge-pressed)))' : st.press ? 'scale(.97)' : 'none',
    filter: st.hover && !v.bgHover && !d ? 'brightness(.92)' : 'none',
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
    font: s.font,
    whiteSpace: 'nowrap',
    textDecoration: 'none',
    cursor: d ? 'not-allowed' : st.loading ? 'progress' : 'pointer',
    transition: 'var(--transition-interactive)'
  };
}
function Button({
  children,
  variant = 'primary',
  size = 'md',
  iconLeft,
  iconRight,
  fullWidth,
  disabled,
  loading,
  type = 'button',
  onClick,
  style
}) {
  const [st, h] = useInteractive(disabled || loading);
  const s = buttonSizes[size] || buttonSizes.md;
  const ic = n => typeof n === 'string' ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: n,
    size: s.icon
  }) : n;
  return /*#__PURE__*/React.createElement("button", _extends({
    type: type,
    disabled: disabled,
    "aria-busy": loading || undefined,
    onClick: loading ? undefined : onClick
  }, h, {
    style: {
      ...buttonStyle(variant, size, {
        ...st,
        disabled,
        loading
      }),
      padding: '0 ' + s.px,
      gap: s.gap,
      width: fullWidth ? '100%' : undefined,
      ...style
    }
  }), loading ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "loader-circle",
    size: s.icon,
    spin: true
  }) : iconLeft && ic(iconLeft), children, !loading && iconRight && ic(iconRight));
}
Object.assign(__ds_scope, { useInteractive, buttonVariants, buttonSizes, buttonStyle, Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Button.jsx", error: String((e && e.message) || e) }); }

// components/core/IconButton.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function IconButton({
  icon,
  size = 'md',
  variant = 'outline',
  shape = 'square',
  label,
  onClick,
  active,
  disabled,
  style
}) {
  const [st, h] = __ds_scope.useInteractive(disabled);
  const s = __ds_scope.buttonSizes[size] || __ds_scope.buttonSizes.md;
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    "aria-label": label,
    title: label,
    "aria-pressed": active === undefined ? undefined : !!active,
    disabled: disabled,
    onClick: onClick
  }, h, {
    style: {
      ...__ds_scope.buttonStyle(active ? 'primary' : variant, size, {
        ...st,
        disabled
      }),
      width: s.h,
      padding: 0,
      borderRadius: shape === 'circle' ? 'var(--radius-pill)' : s.radius,
      ...style
    }
  }), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: s.icon
  }));
}
Object.assign(__ds_scope, { IconButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/IconButton.jsx", error: String((e && e.message) || e) }); }

// components/core/Pill.jsx
try { (() => {
const T = {
  neutral: ['var(--surface-card)', 'var(--text-strong)', 'var(--border-default)'],
  primary: ['var(--primary-bg)', 'var(--primary-text)', 'var(--primary-border)'],
  secondary: ['var(--secondary-bg)', 'var(--secondary-text)', 'var(--secondary-border)'],
  success: ['var(--success-bg)', 'var(--success-text)', 'var(--success-border)'],
  warning: ['var(--warning-bg)', 'var(--warning-text)', 'var(--warning-border)'],
  danger: ['var(--danger-bg)', 'var(--danger-text)', 'var(--danger-border)'],
  info: ['var(--info-bg)', 'var(--info-text)', 'var(--info-border)']
};
function Pill({
  children,
  tone = 'primary',
  icon,
  size = 'md'
}) {
  const [bg, c, b] = T[tone] || T.primary,
    sm = size === 'sm';
  return /*#__PURE__*/React.createElement("span", {
    style: {
      background: bg,
      color: c,
      border: 'var(--border-width-thin) solid ' + b,
      display: 'inline-flex',
      alignItems: 'center',
      gap: 'var(--space-2)',
      height: sm ? 'var(--space-7)' : 'var(--control-h-sm)',
      padding: sm ? '0 var(--space-3)' : '0 var(--space-4)',
      borderRadius: 'var(--radius-pill)',
      font: sm ? 'var(--type-label-sm)' : 'var(--type-label)',
      whiteSpace: 'nowrap'
    }
  }, icon, children);
}
Object.assign(__ds_scope, { Pill });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Pill.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Alert.jsx
try { (() => {
const I = {
  success: 'circle-check',
  warning: 'triangle-alert',
  danger: 'circle-x',
  info: 'info'
};
function Alert({
  tone = 'info',
  title,
  children,
  icon
}) {
  return /*#__PURE__*/React.createElement("div", {
    role: tone === 'danger' || tone === 'warning' ? 'alert' : 'status',
    style: {
      display: 'flex',
      gap: 'var(--space-3)',
      alignItems: 'flex-start',
      padding: 'var(--space-4) var(--space-5)',
      borderRadius: 'var(--radius-md)',
      background: 'var(--' + tone + '-bg)',
      border: 'var(--border-width-thin) solid var(--' + tone + '-border)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon || I[tone],
    size: "lg",
    color: 'var(--' + tone + '-text)'
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-1)'
    }
  }, title && /*#__PURE__*/React.createElement("b", {
    style: {
      font: 'var(--fw-semibold) var(--fs-md)/var(--lh-snug) var(--font-sans)',
      color: 'var(--' + tone + '-text)'
    }
  }, title), children && /*#__PURE__*/React.createElement("span", {
    style: {
      font: 'var(--type-body-sm)',
      color: 'var(--text-body)'
    }
  }, children)));
}
Object.assign(__ds_scope, { Alert });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Alert.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const SZ = {
  sm: ['var(--space-5)', 'sm'],
  md: ['var(--space-6)', 'sm'],
  lg: ['var(--space-7)', 'md']
};
function Checkbox({
  checked,
  onChange,
  size = 'md',
  disabled,
  decorative,
  label
}) {
  const [st, h] = __ds_scope.useInteractive(disabled || decorative);
  const [d, ic] = SZ[size] || SZ.md;
  const toggle = () => {
    if (!disabled && onChange) onChange(!checked);
  };
  const a11y = decorative ? {
    'aria-hidden': true
  } : {
    role: 'checkbox',
    'aria-checked': !!checked,
    'aria-disabled': disabled || undefined,
    'aria-label': label,
    tabIndex: disabled ? -1 : 0,
    onClick: toggle,
    onKeyDown: e => {
      if (e.key === ' ' || e.key === 'Enter') {
        e.preventDefault();
        toggle();
      }
    },
    ...h
  };
  const bc = disabled ? 'var(--border-disabled)' : checked || st.hover ? 'var(--primary)' : 'var(--border-strong)';
  return /*#__PURE__*/React.createElement("span", _extends({}, a11y, {
    style: {
      width: d,
      height: d,
      borderRadius: 'var(--radius-pill)',
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      flex: 'none',
      cursor: decorative ? 'inherit' : disabled ? 'not-allowed' : 'pointer',
      background: checked ? disabled ? 'var(--text-disabled)' : 'var(--primary)' : disabled ? 'var(--surface-disabled)' : 'var(--surface-card)',
      border: 'var(--border-width) solid ' + bc,
      transition: 'var(--transition-interactive)',
      transform: st.press ? 'scale(.92)' : 'none'
    }
  }), checked && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: ic,
    color: "var(--text-on-primary)"
  }));
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/SearchInput.jsx
try { (() => {
function SearchInput({
  placeholder = 'Tìm kiếm...',
  label = 'Tìm kiếm',
  value,
  onChange,
  width = 240,
  disabled,
  shape = 'rounded'
}) {
  const [f, setF] = React.useState(false);
  const [st, h] = __ds_scope.useInteractive(disabled);
  const bc = disabled ? 'var(--border-disabled)' : st.hover ? 'var(--border-hover)' : 'var(--border-default)';
  return /*#__PURE__*/React.createElement("label", {
    onMouseEnter: h.onMouseEnter,
    onMouseLeave: h.onMouseLeave,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-2)',
      width,
      height: 'var(--control-h-md)',
      padding: '0 var(--space-4)',
      borderRadius: shape === 'pill' ? 'var(--radius-pill)' : 'var(--radius-md)',
      background: disabled ? 'var(--surface-disabled)' : 'var(--surface-page)',
      border: 'var(--border-width-thin) solid ' + bc,
      boxShadow: f ? 'var(--focus-ring)' : 'none',
      transition: 'var(--transition-interactive)',
      cursor: disabled ? 'not-allowed' : 'text'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "search",
    size: "md",
    color: disabled ? 'var(--text-disabled)' : 'var(--text-muted)'
  }), /*#__PURE__*/React.createElement("input", {
    type: "search",
    "aria-label": label,
    disabled: disabled,
    value: value,
    onChange: e => onChange && onChange(e.target.value),
    placeholder: placeholder,
    onFocus: () => setF(true),
    onBlur: () => setF(false),
    style: {
      flex: 1,
      minWidth: 0,
      border: 0,
      outline: 0,
      background: 'transparent',
      font: 'var(--type-body)',
      color: disabled ? 'var(--text-disabled)' : 'var(--text-strong)',
      cursor: 'inherit'
    }
  }));
}
Object.assign(__ds_scope, { SearchInput });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/SearchInput.jsx", error: String((e && e.message) || e) }); }

// components/forms/TextInput.jsx
try { (() => {
function TextInput({
  label,
  placeholder,
  value,
  onChange,
  state,
  hint,
  disabled
}) {
  const [f, setF] = React.useState(false);
  const [st, h] = __ds_scope.useInteractive(disabled);
  const id = React.useId();
  const ok = state === 'correct',
    bad = state === 'wrong';
  const bc = disabled ? 'var(--border-disabled)' : ok ? 'var(--success-border)' : bad ? 'var(--danger-border)' : st.hover ? 'var(--border-hover)' : 'var(--border-default)';
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'grid',
      gap: 'var(--space-2)'
    }
  }, label && /*#__PURE__*/React.createElement("span", {
    style: {
      font: 'var(--type-label)',
      color: 'var(--text-body)'
    }
  }, label), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      display: 'block'
    },
    onMouseEnter: h.onMouseEnter,
    onMouseLeave: h.onMouseLeave
  }, /*#__PURE__*/React.createElement("input", {
    value: value,
    disabled: disabled,
    "aria-invalid": bad || undefined,
    "aria-describedby": hint ? id : undefined,
    onChange: e => onChange && onChange(e.target.value),
    placeholder: placeholder,
    onFocus: () => setF(true),
    onBlur: () => setF(false),
    style: {
      width: '100%',
      height: 'var(--control-h-lg)',
      padding: state ? '0 var(--space-9) 0 var(--space-4)' : '0 var(--space-4)',
      borderRadius: 'var(--radius-md)',
      border: 'var(--border-width-thin) solid ' + bc,
      outline: 0,
      background: disabled ? 'var(--surface-disabled)' : ok ? 'var(--success-bg)' : bad ? 'var(--danger-bg)' : 'var(--surface-card)',
      boxShadow: f ? 'var(--focus-ring)' : 'none',
      font: 'var(--fw-regular) var(--fs-xl)/var(--lh-snug) var(--font-sans)',
      color: disabled ? 'var(--text-disabled)' : 'var(--text-strong)',
      transition: 'var(--transition-interactive)',
      cursor: disabled ? 'not-allowed' : 'text'
    }
  }), state && /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      right: 'var(--space-4)',
      top: '50%',
      transform: 'translateY(-50%)',
      display: 'flex'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: ok ? 'circle-check' : 'circle-x',
    size: "lg",
    color: ok ? 'var(--success-text)' : 'var(--danger-text)'
  }))), hint && /*#__PURE__*/React.createElement("span", {
    id: id,
    style: {
      font: 'var(--type-body-sm)',
      color: ok ? 'var(--success-text)' : bad ? 'var(--danger-text)' : 'var(--text-muted)'
    }
  }, hint));
}
Object.assign(__ds_scope, { TextInput });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/TextInput.jsx", error: String((e && e.message) || e) }); }

// components/learning/AnswerCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const srOnly = {
  position: 'absolute',
  width: 1,
  height: 1,
  overflow: 'hidden',
  clip: 'rect(0 0 0 0)',
  whiteSpace: 'nowrap'
};
function AnswerCard({
  children,
  selected,
  state,
  disabled,
  onClick
}) {
  const [st, h] = __ds_scope.useInteractive(disabled);
  const ok = state === 'correct',
    bad = state === 'wrong';
  let bg = 'var(--surface-card)',
    bc = st.hover ? 'var(--border-hover)' : 'var(--border-default)';
  if (selected) {
    bg = 'var(--primary-bg)';
    bc = 'var(--primary-border)';
  }
  if (ok) {
    bg = 'var(--success-bg)';
    bc = 'var(--success-border)';
  }
  if (bad) {
    bg = 'var(--danger-bg)';
    bc = 'var(--danger-border)';
  }
  const lift = st.hover && !selected && !state;
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    disabled: disabled,
    "aria-pressed": !!selected,
    onClick: onClick
  }, h, {
    style: {
      position: 'relative',
      minHeight: 160,
      width: '100%',
      padding: 'var(--space-6) var(--space-7)',
      borderRadius: 'var(--radius-md)',
      background: bg,
      border: 'var(--border-width-thin) solid ' + bc,
      cursor: disabled ? 'default' : 'pointer',
      textAlign: 'center',
      font: 'var(--type-answer)',
      color: 'var(--text-strong)',
      boxShadow: lift ? 'var(--shadow-card)' : 'none',
      transform: lift && !st.press ? 'translateY(-2px)' : 'none',
      transition: 'var(--transition-interactive)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: 'var(--space-3)',
      right: 'var(--space-3)',
      display: 'flex'
    }
  }, state ? /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      width: 'var(--space-7)',
      height: 'var(--space-7)',
      borderRadius: 'var(--radius-pill)',
      background: ok ? 'var(--success)' : 'var(--danger)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: ok ? 'check' : 'x',
    size: "md",
    color: "var(--white)"
  })) : /*#__PURE__*/React.createElement(__ds_scope.Checkbox, {
    checked: !!selected,
    decorative: true,
    size: "lg"
  })), children, state && /*#__PURE__*/React.createElement("span", {
    style: srOnly
  }, ok ? ' — đáp án đúng' : ' — đáp án sai'));
}
Object.assign(__ds_scope, { AnswerCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/AnswerCard.jsx", error: String((e && e.message) || e) }); }

// components/learning/AudioButton.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function AudioButton({
  size = 'lg',
  text,
  lang = 'en-US',
  disabled,
  tone
}) {
  const [p, setP] = React.useState(false);
  const [st, h] = __ds_scope.useInteractive(disabled);
  const lg = size === 'lg',
    d = lg ? 'var(--control-h-xl)' : 'var(--control-h-sm)';
  const play = () => {
    setP(true);
    try {
      const u = new SpeechSynthesisUtterance(text || '');
      u.lang = lang;
      u.rate = .85;
      u.onend = () => setP(false);
      speechSynthesis.speak(u);
    } catch (e) {}
    setTimeout(() => setP(false), 1200);
  };
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    onClick: play,
    disabled: disabled,
    "aria-label": 'Nghe phát âm ' + (text || '')
  }, h, {
    style: {
      width: d,
      height: d,
      flex: 'none',
      borderRadius: 'var(--radius-pill)',
      border: 0,
      cursor: disabled ? 'not-allowed' : 'pointer',
      background: disabled ? 'var(--surface-disabled)' : tone ? 'var(--' + tone + ')' : st.hover ? 'var(--primary-border)' : 'var(--primary-bg-hover)',
      color: disabled ? 'var(--text-disabled)' : tone ? tone === 'accent' ? 'var(--text-on-accent)' : 'var(--white)' : 'var(--primary)',
      filter: tone && st.hover && !disabled ? 'brightness(.92)' : 'none',
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      transform: st.press ? 'scale(.94)' : p ? 'scale(1.06)' : 'none',
      boxShadow: p ? 'var(--shadow-halo)' : 'none',
      transition: 'var(--transition-interactive)'
    }
  }), /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "volume-2",
    size: lg ? 'xl' : 'md'
  }));
}
Object.assign(__ds_scope, { AudioButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/AudioButton.jsx", error: String((e && e.message) || e) }); }

// components/learning/Flashcard.jsx
try { (() => {
function Flashcard({
  word,
  phonetic,
  meaning,
  image,
  tone = 'primary'
}) {
  return /*#__PURE__*/React.createElement("article", {
    style: {
      display: 'grid',
      gridTemplateColumns: '104px minmax(0, 1fr) auto',
      alignItems: 'center',
      gap: 'var(--space-4)',
      padding: 'var(--space-5)',
      minHeight: 144,
      borderRadius: 'var(--radius-lg)',
      background: 'var(--' + tone + '-bg)',
      border: 'var(--border-width-thin) solid var(--' + tone + '-border)'
    }
  }, image ? /*#__PURE__*/React.createElement("img", {
    src: image,
    alt: "",
    style: {
      width: 104,
      height: 104,
      objectFit: 'contain'
    }
  }) : /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      width: 104,
      height: 104,
      borderRadius: 'var(--radius-md)',
      border: 'var(--border-width-thin) dashed var(--' + tone + '-border)',
      background: 'var(--surface-card)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: 'var(--text-disabled)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "image",
    size: "xl"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-1)'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    lang: "en",
    style: {
      margin: 0,
      font: 'var(--type-h2)',
      color: 'var(--' + tone + '-text)'
    }
  }, word), phonetic && /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--type-body-lg)',
      color: 'var(--primary)',
      whiteSpace: 'nowrap'
    }
  }, phonetic), meaning && /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--type-body)',
      color: 'var(--text-strong)',
      marginTop: 'var(--space-2)'
    }
  }, meaning)), /*#__PURE__*/React.createElement("span", {
    style: {
      alignSelf: 'start'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.AudioButton, {
    size: "sm",
    text: word,
    tone: tone
  })));
}
Object.assign(__ds_scope, { Flashcard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/Flashcard.jsx", error: String((e && e.message) || e) }); }

// components/learning/LessonCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function LessonCard({
  title,
  description,
  thumbnail,
  thumbnailFit = 'cover',
  tone = 'primary',
  href,
  onClick
}) {
  const [st, h] = __ds_scope.useInteractive(false);
  return /*#__PURE__*/React.createElement("a", _extends({
    href: href || '#',
    onClick: e => {
      if (onClick) {
        if (!href) e.preventDefault();
        onClick(e);
      }
    }
  }, h, {
    style: {
      display: 'flex',
      flexDirection: 'column',
      width: '100%',
      padding: 0,
      textAlign: 'left',
      textDecoration: 'none',
      color: 'inherit',
      font: 'inherit',
      overflow: 'hidden',
      cursor: 'pointer',
      background: 'var(--surface-card)',
      border: 'var(--border-width-thin) solid var(--border-subtle)',
      borderRadius: 'var(--radius-lg)',
      boxShadow: st.hover ? 'var(--shadow-raised)' : 'var(--shadow-card)',
      transform: st.hover && !st.press ? 'translateY(-2px)' : 'none',
      transition: 'var(--transition-interactive)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      aspectRatio: '4 / 3',
      width: '100%',
      background: 'var(--' + tone + '-bg)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden'
    }
  }, thumbnail ? /*#__PURE__*/React.createElement("img", {
    src: thumbnail,
    alt: "",
    style: {
      width: '100%',
      height: '100%',
      objectFit: thumbnailFit,
      padding: thumbnailFit === 'contain' ? 'var(--space-6)' : 0,
      transform: st.hover ? 'scale(1.04)' : 'none',
      transition: 'transform var(--dur-base) var(--ease-out)'
    }
  }) : /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "image",
    size: "xl",
    color: "var(--text-disabled)"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-2)',
      padding: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      font: 'var(--type-h3)',
      color: st.hover ? 'var(--primary-text)' : 'var(--text-strong)',
      transition: 'var(--transition-interactive)'
    }
  }, title), description && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      font: 'var(--type-body-sm)',
      color: 'var(--text-muted)',
      display: '-webkit-box',
      WebkitLineClamp: 2,
      WebkitBoxOrient: 'vertical',
      overflow: 'hidden'
    }
  }, description)));
}
Object.assign(__ds_scope, { LessonCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/LessonCard.jsx", error: String((e && e.message) || e) }); }

// components/learning/ProgressBar.jsx
try { (() => {
function ProgressBar({
  value = 0,
  max = 100,
  tone = 'primary',
  height = 'var(--space-2)',
  label
}) {
  const pct = Math.max(0, Math.min(100, value / max * 100));
  return /*#__PURE__*/React.createElement("div", {
    role: "progressbar",
    "aria-valuenow": value,
    "aria-valuemin": 0,
    "aria-valuemax": max,
    "aria-label": label,
    style: {
      height,
      borderRadius: 'var(--radius-pill)',
      background: 'var(--surface-track)',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      width: pct + '%',
      height: '100%',
      borderRadius: 'var(--radius-pill)',
      background: 'var(--' + tone + ')',
      transition: 'width var(--dur-slow) var(--ease-out)'
    }
  }));
}
Object.assign(__ds_scope, { ProgressBar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/ProgressBar.jsx", error: String((e && e.message) || e) }); }

// components/learning/ProgressRing.jsx
try { (() => {
function ProgressRing({
  value = 0,
  size = 176,
  stroke = 16,
  label = 'Hoàn thành'
}) {
  const r = (size - stroke) / 2,
    C = 2 * Math.PI * r,
    off = C * (1 - value / 100);
  return /*#__PURE__*/React.createElement("div", {
    role: "img",
    "aria-label": label + ' ' + value + '%',
    style: {
      position: 'relative',
      width: size,
      height: size
    }
  }, /*#__PURE__*/React.createElement("svg", {
    width: size,
    height: size,
    style: {
      transform: 'rotate(-90deg)'
    },
    "aria-hidden": "true"
  }, /*#__PURE__*/React.createElement("circle", {
    cx: size / 2,
    cy: size / 2,
    r: r,
    fill: "none",
    stroke: "var(--primary-border)",
    strokeWidth: stroke
  }), /*#__PURE__*/React.createElement("circle", {
    cx: size / 2,
    cy: size / 2,
    r: r,
    fill: "none",
    stroke: "var(--primary)",
    strokeWidth: stroke,
    strokeLinecap: "round",
    strokeDasharray: C,
    strokeDashoffset: off,
    style: {
      transition: 'stroke-dashoffset var(--dur-slow) var(--ease-out)'
    }
  })), /*#__PURE__*/React.createElement("div", {
    "aria-hidden": "true",
    style: {
      position: 'absolute',
      inset: 0,
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 'var(--space-2)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--fw-bold)',
      fontSize: size * 0.27,
      lineHeight: 'var(--lh-none)',
      color: 'var(--text-strong)'
    }
  }, value, /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: size * 0.16
    }
  }, "%")), /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--type-body-sm)',
      color: 'var(--text-body)'
    }
  }, label)));
}
Object.assign(__ds_scope, { ProgressRing });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/ProgressRing.jsx", error: String((e && e.message) || e) }); }

// components/learning/SpeechBubble.jsx
try { (() => {
function SpeechBubble({
  children,
  tail = 'left'
}) {
  return /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      display: 'inline-block',
      padding: 'var(--space-2) var(--space-4)',
      background: 'var(--surface-card)',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-card)',
      font: 'var(--type-label-lg)',
      color: 'var(--primary-text)',
      textAlign: 'center'
    }
  }, children, /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      position: 'absolute',
      bottom: 'calc(var(--space-2) * -1)',
      [tail]: 'var(--space-5)',
      width: 'var(--space-4)',
      height: 'var(--space-4)',
      background: 'var(--surface-card)',
      transform: 'rotate(45deg)',
      borderRadius: 'var(--radius-xs)'
    }
  }));
}
Object.assign(__ds_scope, { SpeechBubble });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/SpeechBubble.jsx", error: String((e && e.message) || e) }); }

// components/learning/StatRow.jsx
try { (() => {
function StatRow({
  icon,
  label,
  value,
  max,
  tone = 'primary',
  done
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'var(--space-8) 1fr var(--space-6)',
      alignItems: 'center',
      gap: 'var(--space-3)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'flex',
      justifyContent: 'center'
    }
  }, icon), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-2)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      font: 'var(--type-body)',
      color: 'var(--text-strong)'
    }
  }, /*#__PURE__*/React.createElement("span", null, label), /*#__PURE__*/React.createElement("span", null, value, "/", max)), /*#__PURE__*/React.createElement(__ds_scope.ProgressBar, {
    value: value,
    max: max,
    tone: tone,
    label: label
  })), /*#__PURE__*/React.createElement("span", {
    role: "img",
    "aria-label": done ? 'Đã hoàn thành' : 'Chưa hoàn thành',
    style: {
      width: 'var(--space-6)',
      height: 'var(--space-6)',
      borderRadius: 'var(--radius-pill)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: done ? 'var(--success)' : 'var(--surface-card)',
      border: 'var(--border-width) solid ' + (done ? 'var(--success)' : 'var(--border-strong)')
    }
  }, done && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: "sm",
    color: "var(--white)"
  })));
}
Object.assign(__ds_scope, { StatRow });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/StatRow.jsx", error: String((e && e.message) || e) }); }

// components/learning/TipCard.jsx
try { (() => {
function TipCard({
  title = 'Mẹo học nhanh',
  children,
  icon = 'lightbulb'
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-2)',
      padding: 'var(--space-5)',
      background: 'var(--info-bg)',
      borderRadius: 'var(--radius-md)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-2)',
      font: 'var(--type-h3)',
      color: 'var(--text-strong)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: "lg",
    color: "var(--info)"
  }), title), /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--type-body-sm)',
      color: 'var(--text-muted)'
    }
  }, children));
}
Object.assign(__ds_scope, { TipCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/TipCard.jsx", error: String((e && e.message) || e) }); }

// components/learning/WordPrompt.jsx
try { (() => {
function WordPrompt({
  word,
  phonetic
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.AudioButton, {
    text: word
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gap: 'var(--space-2)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    lang: "en",
    style: {
      font: 'var(--type-word)',
      color: 'var(--text-strong)',
      letterSpacing: 'var(--ls-tight)'
    }
  }, word), phonetic && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-2)',
      font: 'var(--type-body-lg)',
      color: 'var(--text-muted)'
    }
  }, phonetic, /*#__PURE__*/React.createElement(__ds_scope.AudioButton, {
    size: "sm",
    text: word
  }))));
}
Object.assign(__ds_scope, { WordPrompt });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/learning/WordPrompt.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Breadcrumbs.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Crumb({
  it,
  i,
  onNavigate
}) {
  const [st, h] = __ds_scope.useInteractive(false);
  return /*#__PURE__*/React.createElement("a", _extends({
    href: it.href || '#',
    onClick: e => {
      if (onNavigate) {
        e.preventDefault();
        onNavigate(i);
      }
    }
  }, h, {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      color: st.hover ? 'var(--text-link-hover)' : 'var(--text-link)',
      textDecoration: st.hover ? 'underline' : 'none',
      transition: 'var(--transition-interactive)'
    }
  }), it.label);
}
function Breadcrumbs({
  items = [],
  onNavigate
}) {
  return /*#__PURE__*/React.createElement("nav", {
    "aria-label": "Breadcrumb"
  }, /*#__PURE__*/React.createElement("ol", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      flexWrap: 'wrap',
      margin: 0,
      padding: 0,
      listStyle: 'none',
      font: 'var(--type-body)'
    }
  }, items.map((it, i) => {
    const last = i === items.length - 1;
    return /*#__PURE__*/React.createElement("li", {
      key: i,
      style: {
        display: 'flex',
        alignItems: 'center',
        gap: 'var(--space-3)'
      }
    }, last ? /*#__PURE__*/React.createElement("span", {
      "aria-current": "page",
      style: {
        font: 'var(--type-label)',
        color: 'var(--text-strong)'
      }
    }, it.label) : /*#__PURE__*/React.createElement(Crumb, {
      it: it,
      i: i,
      onNavigate: onNavigate
    }), !last && /*#__PURE__*/React.createElement("span", {
      "aria-hidden": "true",
      style: {
        color: 'var(--text-disabled)'
      }
    }, "/"));
  })));
}
Object.assign(__ds_scope, { Breadcrumbs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Breadcrumbs.jsx", error: String((e && e.message) || e) }); }

// components/navigation/NavBar.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function NavItem({
  it,
  on,
  onSelect
}) {
  const [st, h] = __ds_scope.useInteractive(false);
  return /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    "aria-current": on ? 'page' : undefined,
    onClick: () => onSelect && onSelect(it.id)
  }, h, {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-2)',
      height: 'var(--control-h-md)',
      padding: '0 var(--space-4)',
      border: 0,
      borderRadius: 'var(--radius-pill)',
      cursor: 'pointer',
      whiteSpace: 'nowrap',
      background: on ? 'var(--primary-bg)' : st.hover ? 'var(--surface-hover)' : 'transparent',
      font: on ? 'var(--type-label)' : 'var(--type-body)',
      color: on ? 'var(--primary-text)' : 'var(--text-body)',
      transform: st.press ? 'translateY(1px)' : 'none',
      transition: 'var(--transition-interactive)'
    }
  }), it.icon && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: it.icon,
    size: "md",
    color: on ? 'var(--primary)' : 'var(--text-muted)'
  }), it.label);
}
function NavBar({
  items = [],
  active,
  onSelect,
  logoSrc,
  avatarSrc,
  notifications = 0
}) {
  return /*#__PURE__*/React.createElement("header", {
    style: {
      background: 'var(--surface-header)',
      borderBottom: 'var(--border-width-thin) solid var(--border-subtle)',
      boxShadow: 'var(--shadow-xs)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: 'var(--layout-header-h)',
      maxWidth: 'var(--layout-max-w)',
      margin: '0 auto',
      padding: '0 var(--space-8)',
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-8)'
    }
  }, /*#__PURE__*/React.createElement("a", {
    href: "#",
    "aria-label": "FunEnglish \u2014 Trang ch\u1EE7",
    onClick: e => {
      e.preventDefault();
      onSelect && items[0] && onSelect(items[0].id);
    },
    style: {
      display: 'flex',
      alignItems: 'center',
      flex: 'none',
      textDecoration: 'none'
    }
  }, logoSrc ? /*#__PURE__*/React.createElement("img", {
    src: logoSrc,
    alt: "",
    style: {
      height: 'var(--control-h-lg)'
    }
  }) : /*#__PURE__*/React.createElement("b", {
    style: {
      font: 'var(--fw-bold) var(--fs-2xl)/var(--lh-none) var(--font-sans)',
      letterSpacing: 'var(--ls-tight)',
      color: 'var(--primary)'
    }
  }, "FunEnglish")), /*#__PURE__*/React.createElement("nav", {
    "aria-label": "Ch\xEDnh",
    style: {
      display: 'flex',
      gap: 'var(--space-1)',
      flex: 1,
      minWidth: 0
    }
  }, items.map(it => /*#__PURE__*/React.createElement(NavItem, {
    key: it.id,
    it: it,
    on: it.id === active,
    onSelect: onSelect
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      flex: 'none'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.SearchInput, {
    placeholder: "T\xECm ki\u1EBFm...",
    width: 240,
    shape: "pill"
  }), /*#__PURE__*/React.createElement(__ds_scope.Badge, {
    count: notifications,
    inset: true
  }, /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "bell",
    label: 'Thông báo' + (notifications ? ' (' + notifications + ' mới)' : ''),
    variant: "ghost",
    shape: "circle"
  })), /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      width: 'var(--border-width-thin)',
      height: 'var(--space-7)',
      background: 'var(--border-default)'
    }
  }), avatarSrc ? /*#__PURE__*/React.createElement("button", {
    type: "button",
    "aria-label": "T\xE0i kho\u1EA3n",
    style: {
      padding: 0,
      border: 0,
      background: 'none',
      cursor: 'pointer',
      borderRadius: 'var(--radius-pill)',
      display: 'flex'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: avatarSrc,
    alt: "",
    style: {
      width: 'var(--control-h-md)',
      height: 'var(--control-h-md)',
      borderRadius: 'var(--radius-pill)',
      border: 'var(--border-width) solid var(--primary-border)',
      objectFit: 'cover'
    }
  })) : /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "user",
    label: "T\xE0i kho\u1EA3n",
    variant: "soft",
    shape: "circle"
  }))));
}
Object.assign(__ds_scope, { NavBar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/NavBar.jsx", error: String((e && e.message) || e) }); }

// components/navigation/StepTabs.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function Step({
  i,
  label,
  status,
  onChange,
  total
}) {
  const [st, h] = __ds_scope.useInteractive(false);
  const on = status === 'current',
    done = status === 'done';
  const dot = on ? {
    background: 'var(--secondary)',
    borderColor: 'var(--secondary)',
    color: 'var(--text-on-secondary)',
    boxShadow: '0 0 0 4px var(--secondary-bg-hover)'
  } : done ? {
    background: 'var(--success)',
    borderColor: 'var(--success)',
    color: 'var(--white)',
    boxShadow: 'none'
  } : {
    background: 'var(--surface-card)',
    borderColor: st.hover ? 'var(--secondary)' : 'var(--border-strong)',
    color: st.hover ? 'var(--secondary-text)' : 'var(--text-muted)',
    boxShadow: 'none'
  };
  return /*#__PURE__*/React.createElement("li", {
    style: {
      display: 'contents'
    }
  }, /*#__PURE__*/React.createElement("button", _extends({
    type: "button",
    "aria-current": on ? 'step' : undefined,
    "aria-label": 'Bước ' + (i + 1) + ' trên ' + total + ': ' + label + (done ? ' (đã xong)' : ''),
    onClick: () => onChange && onChange(i)
  }, h, {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      minHeight: 'var(--control-h-md)',
      padding: '0 var(--space-2)',
      margin: '0 calc(var(--space-2) * -1)',
      border: 0,
      background: 'transparent',
      borderRadius: 'var(--radius-sm)',
      cursor: 'pointer',
      flex: 'none',
      transform: st.press ? 'translateY(1px)' : 'none',
      transition: 'var(--transition-interactive)'
    }
  }), /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      width: 'var(--space-7)',
      height: 'var(--space-7)',
      borderRadius: 'var(--radius-pill)',
      border: 'var(--border-width) solid',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      font: 'var(--fw-semibold) var(--fs-sm)/var(--lh-none) var(--font-sans)',
      flex: 'none',
      transition: 'var(--transition-interactive)',
      ...dot
    }
  }, done ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: "sm"
  }) : i + 1), /*#__PURE__*/React.createElement("span", {
    style: {
      font: on ? 'var(--type-label)' : 'var(--type-body)',
      color: on ? 'var(--text-strong)' : done || st.hover ? 'var(--text-body)' : 'var(--text-muted)',
      whiteSpace: 'nowrap'
    }
  }, label)));
}
function StepTabs({
  steps = [],
  active = 0,
  completed,
  onChange,
  label = 'Các bước'
}) {
  const isDone = i => completed ? completed.includes(i) : i < active;
  const onKey = e => {
    const n = steps.length;
    if (!onChange) return;
    if (e.key === 'ArrowRight') onChange(Math.min(n - 1, active + 1));
    if (e.key === 'ArrowLeft') onChange(Math.max(0, active - 1));
  };
  return /*#__PURE__*/React.createElement("nav", {
    "aria-label": label,
    onKeyDown: onKey
  }, /*#__PURE__*/React.createElement("ol", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      margin: 0,
      padding: 0,
      listStyle: 'none'
    }
  }, steps.map((s, i) => /*#__PURE__*/React.createElement(React.Fragment, {
    key: i
  }, i > 0 && /*#__PURE__*/React.createElement("li", {
    "aria-hidden": "true",
    style: {
      flex: 1,
      minWidth: 'var(--space-5)',
      height: 'var(--border-width)',
      borderRadius: 'var(--radius-pill)',
      background: isDone(i - 1) ? 'var(--success)' : 'var(--border-default)'
    }
  }), /*#__PURE__*/React.createElement(Step, {
    i: i,
    label: s,
    total: steps.length,
    status: i === active ? 'current' : isDone(i) ? 'done' : 'todo',
    onChange: onChange
  })))));
}
Object.assign(__ds_scope, { StepTabs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/StepTabs.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/App.jsx
try { (() => {
(() => {
  const {
    NavBar,
    Breadcrumbs,
    Card
  } = window.FunEnglishDesignSystem_41741d;
  function App() {
    const D = window.FE_DATA,
      total = D.questions.length;
    const [page, setPage] = React.useState('courses');
    const [qIndex, setQ] = React.useState(0);
    const [step, setStep] = React.useState(0);
    const [answers, setAnswers] = React.useState({});
    const [graded, setGraded] = React.useState({});
    const [saved, setSaved] = React.useState({});
    const q = D.questions[qIndex];
    const vals = Object.keys(graded).map(k => answers[k] === D.questions[k].answer);
    const correct = vals.filter(Boolean).length,
      wrong = vals.length - correct;
    const actions = {
      setStep,
      select: i => setAnswers(a => ({
        ...a,
        [qIndex]: i
      })),
      prev: () => setQ(i => Math.max(0, i - 1)),
      next: () => {
        if (!graded[qIndex]) setGraded(g => ({
          ...g,
          [qIndex]: true
        }));else if (qIndex < total - 1) setQ(qIndex + 1);
      },
      toggleSave: () => setSaved(s => ({
        ...s,
        [qIndex]: !s[qIndex]
      }))
    };
    const state = {
      qIndex,
      total,
      score: correct * 10,
      step,
      selected: answers[qIndex],
      graded: !!graded[qIndex],
      q,
      saved: !!saved[qIndex]
    };
    return /*#__PURE__*/React.createElement("div", {
      style: {
        minHeight: '100vh',
        background: 'var(--surface-page)'
      }
    }, /*#__PURE__*/React.createElement(NavBar, {
      notifications: 3,
      items: D.nav,
      active: page,
      onSelect: setPage
    }), /*#__PURE__*/React.createElement("main", {
      style: {
        position: 'relative',
        maxWidth: 'var(--layout-max-w)',
        margin: '0 auto',
        padding: 'var(--space-7) var(--space-8) var(--space-9)'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        marginBottom: 'var(--space-7)',
        paddingLeft: 'var(--space-2)'
      }
    }, /*#__PURE__*/React.createElement(Breadcrumbs, {
      items: [{
        label: 'Trang chủ'
      }, {
        label: 'Khóa học tiếng Anh lớp 10'
      }, {
        label: 'Unit 1 - Hello'
      }, {
        label: 'Ôn tập'
      }]
    })), page === 'courses' ? /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'grid',
        gridTemplateColumns: 'minmax(0,1fr) var(--layout-sidebar-w)',
        gap: 'var(--space-5)'
      }
    }, /*#__PURE__*/React.createElement(QuizPanel, {
      state: state,
      actions: actions
    }), /*#__PURE__*/React.createElement(ProgressPanel, {
      learned: Math.min(total, Object.keys(answers).length + 0),
      correct: correct,
      wrong: wrong,
      total: total,
      onContinue: () => actions.next()
    })) : /*#__PURE__*/React.createElement(Card, {
      padding: "lg",
      style: {
        textAlign: 'center',
        color: 'var(--text-muted)'
      }
    }, "M\xE0n h\xECnh n\xE0y kh\xF4ng c\xF3 trong thi\u1EBFt k\u1EBF tham chi\u1EBFu \u2014 ch\u1EC9 trang \xD4n t\u1EADp (Kh\xF3a h\u1ECDc) \u0111\u01B0\u1EE3c d\u1EF1ng l\u1EA1i.", /*#__PURE__*/React.createElement("br", null), /*#__PURE__*/React.createElement("a", {
      href: "#",
      onClick: e => {
        e.preventDefault();
        setPage('courses');
      }
    }, "Quay l\u1EA1i \xD4n t\u1EADp"))));
  }
  function Stage({
    children
  }) {
    const [s, setS] = React.useState(Math.min(1, window.innerWidth / 1536));
    const ref = React.useRef(null);
    const [h, setH] = React.useState(1024);
    React.useEffect(() => {
      const on = () => setS(Math.min(1, window.innerWidth / 1536));
      window.addEventListener('resize', on);
      const ro = new ResizeObserver(() => ref.current && setH(ref.current.offsetHeight));
      ro.observe(ref.current);
      return () => {
        window.removeEventListener('resize', on);
        ro.disconnect();
      };
    }, []);
    return /*#__PURE__*/React.createElement("div", {
      style: {
        height: h * s,
        overflow: 'hidden',
        background: 'var(--surface-page)'
      }
    }, /*#__PURE__*/React.createElement("div", {
      ref: ref,
      style: {
        width: 1536,
        transform: 'scale(' + s + ')',
        transformOrigin: '0 0'
      }
    }, children));
  }
  ReactDOM.createRoot(document.getElementById('root')).render(/*#__PURE__*/React.createElement(Stage, null, /*#__PURE__*/React.createElement(App, null)));
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/App.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/ProgressPanel.jsx
try { (() => {
(() => {
  const {
    ProgressRing,
    StatRow,
    TipCard,
    Button,
    Icon
  } = window.FunEnglishDesignSystem_41741d;
  function ProgressPanel({
    learned,
    correct,
    wrong,
    total,
    onContinue
  }) {
    const pct = Math.round((correct + wrong) / total * 100) || 0;
    const box = {
      background: 'var(--surface-card)',
      borderRadius: 'var(--radius-lg)',
      boxShadow: 'var(--shadow-card)',
      overflow: 'hidden'
    };
    return /*#__PURE__*/React.createElement("aside", {
      style: {
        display: 'grid',
        gap: 'var(--space-5)',
        alignContent: 'start'
      }
    }, /*#__PURE__*/React.createElement("section", {
      style: box
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        padding: 'var(--space-6)',
        borderBottom: 'var(--border-width-thin) solid var(--border-subtle)',
        display: 'grid',
        gap: 'var(--space-5)'
      }
    }, /*#__PURE__*/React.createElement("h2", {
      style: {
        margin: 0,
        font: 'var(--type-h2)',
        color: 'var(--text-strong)'
      }
    }, "Ti\u1EBFn \u0111\u1ED9 \xF4n t\u1EADp"), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        justifyContent: 'center'
      }
    }, /*#__PURE__*/React.createElement(ProgressRing, {
      value: pct
    }))), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'grid',
        gap: 'var(--space-6)',
        padding: 'var(--space-6) var(--space-6) var(--space-7)'
      }
    }, /*#__PURE__*/React.createElement(StatRow, {
      icon: /*#__PURE__*/React.createElement(Icon, {
        name: "book-open",
        size: "xl",
        color: "var(--success)"
      }),
      label: "T\u1EEB v\u1EF1ng \u0111\xE3 h\u1ECDc",
      value: learned,
      max: total,
      tone: "success",
      done: learned >= total
    }), /*#__PURE__*/React.createElement(StatRow, {
      icon: /*#__PURE__*/React.createElement(Icon, {
        name: "circle-check",
        size: "xl",
        color: "var(--primary)"
      }),
      label: "S\u1ED1 c\xE2u tr\u1EA3 l\u1EDDi \u0111\xFAng",
      value: correct,
      max: total,
      tone: "primary"
    }), /*#__PURE__*/React.createElement(StatRow, {
      icon: /*#__PURE__*/React.createElement(Icon, {
        name: "circle-x",
        size: "xl",
        color: "var(--danger)"
      }),
      label: "S\u1ED1 c\xE2u tr\u1EA3 l\u1EDDi sai",
      value: wrong,
      max: total,
      tone: "danger"
    }))), /*#__PURE__*/React.createElement("section", {
      style: {
        ...box,
        padding: 'var(--space-5)',
        display: 'grid',
        gap: 'var(--space-5)'
      }
    }, /*#__PURE__*/React.createElement(TipCard, null, "H\u1ECDc t\u1EEB m\u1EDBi m\u1ED7i ng\xE0y s\u1EBD gi\xFAp b\u1EA1n nh\u1EDB l\xE2u h\u01A1n \u0111\xF3!"), /*#__PURE__*/React.createElement(Button, {
      variant: "accent",
      size: "lg",
      fullWidth: true,
      onClick: onContinue,
      iconLeft: "rocket"
    }, "Ti\u1EBFp t\u1EE5c \xF4n t\u1EADp")));
  }
  window.ProgressPanel = ProgressPanel;
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/ProgressPanel.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/QuizPanel.jsx
try { (() => {
(() => {
  const {
    Pill,
    Icon,
    ProgressBar,
    StepTabs,
    WordPrompt,
    AnswerCard,
    Button,
    IconButton
  } = window.FunEnglishDesignSystem_41741d;
  function LessonHead({
    qIndex,
    total,
    score
  }) {
    return /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        alignItems: 'flex-start',
        gap: 'var(--space-6)',
        padding: 'var(--space-7) var(--space-7) var(--space-6)',
        borderBottom: 'var(--border-width-thin) solid var(--border-subtle)',
        background: 'var(--surface-card)'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        flex: 1,
        display: 'grid',
        gap: 'var(--space-2)'
      }
    }, /*#__PURE__*/React.createElement("h1", {
      style: {
        margin: 0,
        font: 'var(--type-h1)',
        color: 'var(--text-strong)'
      }
    }, "\xD4n t\u1EADp m\u1EDF \u0111\u1EA7u Hello"), /*#__PURE__*/React.createElement("p", {
      style: {
        margin: '0 0 var(--space-4)',
        font: 'var(--type-body-lg)',
        color: 'var(--text-muted)'
      }
    }, "Ch\u1ECDn \u0111\xE1p \xE1n \u0111\xFAng cho m\u1ED7i c\xE2u h\u1ECFi nh\xE9!"), /*#__PURE__*/React.createElement(ProgressBar, {
      value: qIndex + 1,
      max: total,
      label: "Ti\u1EBFn \u0111\u1ED9 b\xE0i"
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: 'var(--space-3)',
        marginTop: 'var(--space-6)'
      }
    }, /*#__PURE__*/React.createElement(Pill, null, "C\xE2u ", qIndex + 1, " / ", total), /*#__PURE__*/React.createElement(Pill, {
      tone: "neutral",
      icon: /*#__PURE__*/React.createElement(Icon, {
        name: "star",
        color: "var(--accent)"
      })
    }, "\u0110i\u1EC3m: ", /*#__PURE__*/React.createElement("b", {
      style: {
        color: 'var(--text-score)',
        fontWeight: 'var(--fw-semibold)'
      }
    }, score))));
  }
  function QuizArea({
    q,
    selected,
    graded,
    onSelect
  }) {
    return /*#__PURE__*/React.createElement("div", {
      style: {
        background: 'var(--surface-quiz)',
        borderRadius: 'var(--radius-lg)',
        padding: 'var(--space-9) var(--space-7) var(--space-7)',
        border: 'var(--border-width-thin) solid var(--accent-border)'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        paddingLeft: 'var(--space-6)',
        marginBottom: 'var(--space-8)'
      }
    }, /*#__PURE__*/React.createElement(WordPrompt, {
      word: q.word,
      phonetic: q.phonetic
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, minmax(0, 1fr))',
        gap: 'var(--space-5)'
      }
    }, q.options.map((o, i) => /*#__PURE__*/React.createElement(AnswerCard, {
      key: q.word + i,
      disabled: graded,
      selected: selected === i && !graded,
      state: graded ? i === q.answer ? 'correct' : selected === i ? 'wrong' : undefined : undefined,
      onClick: () => onSelect(i)
    }, o))));
  }
  function QuizPanel({
    state,
    actions
  }) {
    const {
      qIndex,
      total,
      score,
      step,
      selected,
      graded,
      q
    } = state;
    return /*#__PURE__*/React.createElement("section", {
      style: {
        background: 'var(--surface-card)',
        borderRadius: 'var(--radius-lg)',
        boxShadow: 'var(--shadow-card)',
        overflow: 'hidden'
      }
    }, /*#__PURE__*/React.createElement(LessonHead, {
      qIndex: qIndex,
      total: total,
      score: score
    }), /*#__PURE__*/React.createElement("div", {
      style: {
        padding: 'var(--space-6) var(--space-7) 0',
        display: 'grid',
        gap: 'var(--space-6)'
      }
    }, /*#__PURE__*/React.createElement(StepTabs, {
      steps: window.FE_DATA.steps,
      active: step,
      onChange: actions.setStep
    }), /*#__PURE__*/React.createElement(QuizArea, {
      q: q,
      selected: selected,
      graded: graded,
      onSelect: actions.select
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        alignItems: 'center',
        gap: 'var(--space-4)',
        padding: 'var(--space-5) var(--space-7) var(--space-6)',
        background: 'var(--surface-page)',
        marginTop: 'var(--space-5)'
      }
    }, /*#__PURE__*/React.createElement(Button, {
      variant: "outline",
      iconLeft: "arrow-left",
      onClick: actions.prev,
      disabled: qIndex === 0
    }, "C\xE2u tr\u01B0\u1EDBc"), /*#__PURE__*/React.createElement("div", {
      style: {
        flex: 1
      }
    }), /*#__PURE__*/React.createElement(Button, {
      iconRight: "arrow-right",
      onClick: actions.next,
      disabled: selected == null,
      style: {
        minWidth: 220
      }
    }, graded ? qIndex === total - 1 ? 'Xem kết quả' : 'Câu tiếp theo' : 'Kiểm tra'), /*#__PURE__*/React.createElement(IconButton, {
      icon: "bookmark",
      label: "L\u01B0u c\xE2u h\u1ECFi",
      active: state.saved,
      onClick: actions.toggleSave
    })));
  }
  window.QuizPanel = QuizPanel;
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/QuizPanel.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/data.js
try { (() => {
window.FE_DATA = {
  nav: [{
    id: 'home',
    label: 'Trang chủ',
    icon: 'house'
  }, {
    id: 'courses',
    label: 'Khóa học',
    icon: 'book-open'
  }, {
    id: 'vocab',
    label: 'Từ vựng',
    icon: 'book-a'
  }, {
    id: 'test',
    label: 'Bài kiểm tra',
    icon: 'clipboard-check'
  }, {
    id: 'games',
    label: 'Trò chơi',
    icon: 'gamepad-2'
  }],
  steps: ['Trả lời nghĩa', 'Trả lời từ', 'Nghe và trả lời nghĩa', 'Điền từ tiếng Anh'],
  questions: [{
    word: 'quickly',
    phonetic: '/ˈkwɪkli/',
    options: ['(adverb) một cách nhanh chóng', '(adjective) vui vẻ, hạnh phúc, vui vẻ, hạnh phúc', '(n) quả táo'],
    answer: 0
  }, {
    word: 'hello',
    phonetic: '/həˈloʊ/',
    options: ['(n) tạm biệt', '(interj) xin chào', '(v) chạy'],
    answer: 1
  }, {
    word: 'happy',
    phonetic: '/ˈhæpi/',
    options: ['(n) con mèo', '(adv) chậm rãi', '(adjective) vui vẻ, hạnh phúc'],
    answer: 2
  }, {
    word: 'apple',
    phonetic: '/ˈæpl/',
    options: ['(n) quả táo', '(n) quyển sách', '(v) học'],
    answer: 0
  }, {
    word: 'friend',
    phonetic: '/frend/',
    options: ['(n) giáo viên', '(n) bạn bè', '(adj) to lớn'],
    answer: 1
  }]
};
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/data.js", error: String((e && e.message) || e) }); }

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.Icon = __ds_scope.Icon;

__ds_ns.IconButton = __ds_scope.IconButton;

__ds_ns.Pill = __ds_scope.Pill;

__ds_ns.Alert = __ds_scope.Alert;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.SearchInput = __ds_scope.SearchInput;

__ds_ns.TextInput = __ds_scope.TextInput;

__ds_ns.AnswerCard = __ds_scope.AnswerCard;

__ds_ns.AudioButton = __ds_scope.AudioButton;

__ds_ns.Flashcard = __ds_scope.Flashcard;

__ds_ns.LessonCard = __ds_scope.LessonCard;

__ds_ns.ProgressBar = __ds_scope.ProgressBar;

__ds_ns.ProgressRing = __ds_scope.ProgressRing;

__ds_ns.SpeechBubble = __ds_scope.SpeechBubble;

__ds_ns.StatRow = __ds_scope.StatRow;

__ds_ns.TipCard = __ds_scope.TipCard;

__ds_ns.WordPrompt = __ds_scope.WordPrompt;

__ds_ns.Breadcrumbs = __ds_scope.Breadcrumbs;

__ds_ns.NavBar = __ds_scope.NavBar;

__ds_ns.StepTabs = __ds_scope.StepTabs;

})();
