import { readFileSync } from 'node:fs'
import { getSchema } from '@tiptap/core'
import { buildExtensions } from '../../resources/js/editor.js'

const schema = getSchema(buildExtensions({
    toolbar: ['link'],
    mentionChar: '@',
    suggestion: null,
    openLink: () => {},
}))
const documents = JSON.parse(readFileSync(process.argv[2], 'utf8'))

documents.forEach((document) => {
    schema.nodeFromJSON(document).check()
})

console.log(`ok ${documents.length}`)
