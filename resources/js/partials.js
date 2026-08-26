import {findClosestLivewireComponent, isComponentRootEl, isntElement} from "./utils.js";

document.addEventListener('livewire:init', () => {
    Livewire.interceptMessage(({message, onSuccess}) => {
        onSuccess(({payload, onMorph}) => {
            const applyPartials = () => {
                if (payload.effects?.html) {
                    return
                }

                const partialFragments = payload.effects?.partialFragments ?? {}

                for (const [name, html] of Object.entries(partialFragments)) {
                    try {
                        morphPartial(message, name, html)
                    } catch (error) {
                        console.error(`[powergrid-partials] Failed to morph "${name}"`, error)
                    }
                }
            }

            if (typeof onMorph === 'function') {
                onMorph(applyPartials)
            } else {
                queueMicrotask(applyPartials)
            }
        })
    })
})

function wrapperTagName(el) {
    const parent = el.parentElement
    const tag = parent ? parent.tagName.toLowerCase() : 'div'
    const customElement = window.customElements?.get(tag)

    return customElement?.name || tag
}

function morphPartial(message, name, html) {
    let els = Array.from(
        message.component.el.querySelectorAll(
            `[wire\\:partial="${name}"]`,
        ),
    ).filter(
        (el) =>
            findClosestLivewireComponent(el).id ===
            message.component.id,
    )

    if (!els.length) {
        return
    }

    let el = els[0]

    const getIgnoreKey = (node) => {
        return node.getAttribute('wire:partial.ignore') ||
            node.getAttribute('wire:key') ||
            node.id
    }

    const applyMarkers = (node) => {
        if (node.hasAttribute('wire:partial.ignore')) {
            let key = getIgnoreKey(node)
            if (key && !node.previousSibling?.nodeValue?.includes(`START:PARTIAL-IGNORE:${key}`)) {
                node.before(document.createComment(`START:PARTIAL-IGNORE:${key}`))
                node.after(document.createComment(`END:PARTIAL-IGNORE:${key}`))
            }
        }
        node.querySelectorAll('[wire\\:partial\\.ignore]').forEach(child => {
            let key = getIgnoreKey(child)
            if (key && !child.previousSibling?.nodeValue?.includes(`START:PARTIAL-IGNORE:${key}`)) {
                child.before(document.createComment(`START:PARTIAL-IGNORE:${key}`))
                child.after(document.createComment(`END:PARTIAL-IGNORE:${key}`))
            }
        })
    }

    const capturedContent = new Map()

    const captureContent = (node) => {
        if (node.hasAttribute('wire:partial.ignore')) {
            let key = getIgnoreKey(node)
            if (key) {
                capturedContent.set(key, node.innerHTML)
            }
        }
        node.querySelectorAll('[wire\\:partial\\.ignore]').forEach(child => {
            let key = getIgnoreKey(child)
            if (key) {
                capturedContent.set(key, child.innerHTML)
            }
        })
    }

    captureContent(el)
    applyMarkers(el)

    let wrapper = document.createElement(wrapperTagName(el))

    wrapper.innerHTML = html
    wrapper.__livewire = message.component

    let to = wrapper.firstElementChild

    if (!to) {
        return
    }

    to.__livewire = message.component

    applyMarkers(to)

    to.querySelectorAll('[wire\\:partial\\.ignore]').forEach(node => {
        let key = getIgnoreKey(node)
        if (key && node.innerHTML.includes(`<!--PARTIAL:IGNORE:${key}-->`)) {
            node.innerHTML = capturedContent.get(key)
        }
    })

    if (to.hasAttribute('wire:partial.ignore')) {
        let key = getIgnoreKey(to)
        if (key && to.innerHTML.includes(`<!--PARTIAL:IGNORE:${key}-->`)) {
            to.innerHTML = capturedContent.get(key)
        }
    }

    Livewire.trigger?.('morph', {el, toEl: to, component: message.component})

    window.Alpine.morph(el, to, {
        updating: (el, toEl, childrenOnly, skip) => {
            if (isntElement(el)) {
                return
            }

            if (el.__livewire_replace === true) {
                el.innerHTML = toEl.innerHTML
            }

            if (el.__livewire_replace_self === true) {
                el.outerHTML = toEl.outerHTML

                return skip()
            }

            if (el.hasAttribute('wire:partial.ignore')) {
                return skip()
            }

            if (el.__livewire_ignore === true || el.hasAttribute('wire:ignore')) {
                return skip()
            }

            if (el.__livewire_ignore_self === true || el.hasAttribute('wire:ignore.self')) {
                childrenOnly()
            }

            if (
                isComponentRootEl(el) &&
                el.getAttribute('wire:id') !==
                message.component.id
            ) {
                return skip()
            }

            if (isComponentRootEl(el)) {
                toEl.__livewire = message.component
            }
        },

        added: (addedEl) => {
            if (isntElement(addedEl)) {
                return
            }

            Livewire.trigger?.('morph.added', {el: addedEl})
        },

        key: (el) => {
            if (isntElement(el)) {
                return
            }

            if (el.hasAttribute(`wire:key`)) {
                return el.getAttribute(`wire:key`)
            }

            if (el.hasAttribute(`wire:id`)) {
                return el.getAttribute(`wire:id`)
            }

            return el.id
        },

        lookahead: false,
    })

    Livewire.trigger?.('morphed', {el, component: message.component})
}
