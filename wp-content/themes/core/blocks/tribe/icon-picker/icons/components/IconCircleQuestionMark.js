import * as React from 'react';
const SvgIconCircleQuestionMark = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			fill="currentColor"
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 48a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 60c15.464 0 28-12.536 28-28S47.464 4 32 4 4 16.536 4 32s12.536 28 28 28Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M24.018 22.95c1.238-4.716 5.084-7.158 9.39-6.936 4.252.218 8.21 2.558 8.028 7.984-.26 7.714-8.458 6.668-9.374 14.002"
		/>
	</svg>
);
export default SvgIconCircleQuestionMark;
