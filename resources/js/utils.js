export const findClosestLivewireComponent = (el) => {
    let closestRoot = Alpine.findClosest(el, (i) => i.__livewire)

    if (!closestRoot) {
        throw 'Could not find Livewire component in DOM tree.'
    }

    return closestRoot.__livewire
}

export const isntElement = (el) => {
    return typeof el.hasAttribute !== 'function'
}

export const isComponentRootEl = (el) => {
    return el.hasAttribute('wire:id')
}
